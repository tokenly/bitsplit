<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateField;
use App\Models\SignupField;
use App\Models\SignupFieldCondition;
use App\Models\SignupFieldOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class SignupFieldsController extends Controller
{

    public function __construct()
    {
        $this->middleware('tls');
    }

    /**
     * Show the welcome page or redirect
     */
    public function index(Request $request)
    {
        $fields = SignupField::orderBy('position', 'ASC')->orderBy('id', 'ASC')->get();
        $field_types = config('settings.signup_field_types');
        return view('admin.admin-fields', ['this_user' => $request->user(), 'fields' => $fields, 'field_types' => $field_types]);
    }

    public function create(CreateField $request) {
        $field = new SignupField();
        $field->name = $request->input('name');
        $field->type = $request->input('type');
        $field->required = $request->input('required');
        $field->position = SignupField::max('position') + 1;
        $field->save();
        if($field->type === 'Checkbox') {
            $options = $request->input('options');
            foreach ($options as $option_value) {
                $option = new SignupFieldOption();
                $option->value = $option_value;
                $option->field_id = $field->id;
                $option->save();
            }
        }
        if(!empty($request->input('condition_field')) && !empty($request->input('condition'))) {
            $condition = new SignupFieldCondition();
            $condition->field_id = $field->id;
            $condition->field_to_compare_id = $request->input('condition_field');
            $condition->condition = $request->input('condition');
            $condition->save();
        }
        return response()->json(['status' => 'success', 'data' => null], 201);
    }

    public function updateOrder(Request $request) {
        $field_ids = $request->input('fields',[]);
        foreach ($field_ids as $key => $field_id) {
            $field = SignupField::find($field_id);
            $field->position = $key;
            $field->save();
        }
        return response()->json([
            'status' => 'success',
            'data' => null
        ], 200);
    }

    public function delete(Request $request, SignupField $field) {
        $field->delete();
        return response()->json([
            'status' => 'success',
            'data' => null
        ], 200);
    }
}