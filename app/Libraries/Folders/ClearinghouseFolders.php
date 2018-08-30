<?php
namespace App\Libraries\Folders;

use App\Libraries\EscrowWallet\EscrowWalletManager;
use App\Libraries\Folders\Folder;
use App\Libraries\Substation\Substation;
use App\Models\EscrowAddressLedgerEntry;
use App\Repositories\EscrowAddressLedgerEntryRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Tokenly\TokenpassClient\TokenpassAPI;

class ClearinghouseFolders
{

    private $distro_uuid;
    private $folders;

    public function __construct($distro_uuid)
    {
        $this->distro_uuid = $distro_uuid;
    }

    public function getAllFolders()
    {
        if (!isset($this->folders)) {
            $this->folders = $this->calculateAllFolders();
        }

        return $this->folders;
    }

    protected function calculateAllFolders()
    {

        // get all offchain balances
        return DB::transaction(function () {
            $owner = app(UserRepository::class)->findEscrowWalletOwner();
            $escrow_address = app(EscrowWalletManager::class)->getEscrowAddressForUser($owner, Substation::chain());
            $ledger = app(EscrowAddressLedgerEntryRepository::class);

            $asset = FLDCAssetName();

            // get all pending balances
            $balances_by_address = $ledger->allForeignEntityBalancesForAsset($asset);

            // for each balance, credit the ledger to fulfill the promise
            $folders = [];
            foreach ($balances_by_address as $address => $balance) {
                // skip zero (or negative) balances
                if ($balance->lte(0)) {
                    continue;
                }

                // special case: do not fulfill the promise to the fee recover address
                if ($address == env('FOLDINGCOIN_FEE_RECOVERY_ADDRESS')) {
                    continue;
                }

                // credit back the escrow address and fulfill the promise
                $txid = $this->distro_uuid;
                $tx_identifier = 'clrhs:' . $asset . ':' . $this->distro_uuid . ':' . $address;
                $ledger->credit($escrow_address, $balance, $asset, EscrowAddressLedgerEntry::TYPE_PROMISE_FULFILLED, $txid, $tx_identifier, $_confirmed = true, $_promise_id = null, $address);

                // remove the promises
                $this->clearTokenpassPromises($address, $asset);

                // create a Folder instance
                $folder = new Folder(0, $address);
                $folder->setAmountQuantity($balance);
                $folders[] = $folder;
            }

            return $folders;
        });
    }

    protected function clearTokenpassPromises($address, $asset)
    {
        $ledger = app(EscrowAddressLedgerEntryRepository::class);
        $tokenpass = app(TokenpassAPI::class);

        $entries = $ledger->entriesWithPromiseIDsByForeignEntityAndAsset($address, $asset);
        foreach ($entries as $ledger_entry) {
            // cancel the tokenpass promise
            $tokenpass->clearErrors();
            $tokenpass_response = $tokenpass->deletePromisedTransaction($ledger_entry['promise_id']);
            if (!$tokenpass_response) {
                $error_string = $tokenpass->getErrorsAsString();
                $tokenpass->clearErrors();

                if ($error_string == 'Provisional tx not found') {
                    // allow not found provisional transactions
                    EventLog::warn('provisionalTx.notFound', [
                        'promiseId' => $ledger_entry['promise_id'],
                        'address' => $address,
                        'asset' => $asset,
                    ]);
                } else {
                    throw new Exception("Tokenpass deletePromisedTransaction call failed: {$error_string}", 1);
                }
            }

            // remove the promise id from the ledger
            $ledger->update($ledger_entry, [
                'promise_id' => null,
            ]);
        }
    }

}
