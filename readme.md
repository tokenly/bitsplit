#BitSplit

Cryptocurrency token distributor and payment router.

##Installation

On the command line..

```
git clone https://github.com/tokenly/bitsplit.git
cd bitsplit
cp .env.example .env
```

Create your database and fill in all credentials etc. in the ```.env``` file

then

```
composer install
php artisan migrate
```

Go back to your ```.env``` file. Set the ```HOUSE_INCOME_ADDRESS``` variable to a bitcoin address of your choice (this is what all leftover funds are swept to).

For ```MASTER_FUEL_ADDRESS```, this should be an address managed by XChain.   
Generate a new XChain managed address with the command 

```
php artisan bitsplit:newAddress
```

Don't forget to send it some bitcoin.

Almost there. Run the command ```crontab -e```

```
#add the following line to activate the distribution processor
* * * * * php /path/to/bitsplit/artisan schedule:run >> /dev/null 2>&1
```

To view other commands for admin or debug purposes, see the ```bitsplit``` section on command  
```
php artisan list
```
