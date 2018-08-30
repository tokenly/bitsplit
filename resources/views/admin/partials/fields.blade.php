<div class="content padded">

    <a href="{{ route('account.admin.users') }}">
        <i class="fa fa-chevron-left"></i>
        <span>User Dashboard (Admin)</span>
    </a>
    <div class="page-information">
        <h1>User #{{$this_user->id}} (Admin view)</h1>
    </div>

    <div class="page-information">
        <input id="csrf_token" type="hidden" value="{{ csrf_token() }}">
        <h1>Pre-Qualification Fields</h1>
        <p>
            Use the interface below to manage the fields on the sign up form.
        </p>
        <hr>
    </div>
    @if(count($fields) == 0)
        <p>
            No fields found.
        </p>
    @else
        <table class="table table-bordered data-table client-app-table">
            <thead>
            <tr>
                <th>Name</th>
                <th>Type</th>
                <th>Required</th>
                <th></th>
            </tr>
            </thead>
            <tbody class="sortable">
                <tr v-for="field in fields" :id="field.id">
                    <td class="name">@{{  field.name }}</td>
                    <td>@{{ field.type }}</td>
                    <td>
                        <span v-if="field.required">Yes</span>
                        <span v-else>No</span>
                    </td>
                    <td class="table-action">
                        <a @click="deleteField(field.id)" class="btn btn-danger"><i class="fa fa-close"></i> Delete</a>
                    </td>
                </tr>
            </tbody>
            <div id="creation" class="modal fade" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                            <h3 class="modal-title" id="myModalLabel">Create new field</h3>
                        </div>
                        <div class="modal-body">
                            <div class="form-group">
                                <label for="field-name">Field Name</label>
                                <input id="field-name" v-model="field_name" type="text" class="form-control">
                                <span class="text-danger">@{{ errors.name }}</span>
                            </div>
                            <div class="form-group">
                                <label for="field-type">Field Type</label>
                                <select id="field-type" v-model="field_type" class="form-control">
                                    <option v-for="type in available_types" :value="type">@{{ type }}</option>
                                </select>
                            </div>
                            <div v-if="field_type === 'Checkbox'" class="form-group">
                                <label for="field-option">Options</label>
                                <input id="field-option" v-model="new_field_option" type="text" class="form-control">
                                <a @click="addOption" class="btn btn-primary"><i class="fa fa-plus"></i> Add</a>

                                <ul>
                                    <li v-for="option in new_field_options">@{{ option }}</li>
                                </ul>
                            </div>
                            <div class="form-group">
                                <label>Required?</label><br />
                                <ul class="yes-no-toggle">
                                    <li @click="field_required = true"><a><span class="yes" v-bind:class="{active: field_required}">Yes</span></a></li><li class="no" @click="field_required = false"><a><span class="no" v-bind:class="{active: !field_required}">No</span></a></li>
                                </ul>
                            </div>
                            <div class="form-group">
                                <label>Display if</label><br />
                                <select v-model="condition_field" class="form-control">
                                    <option value="">No condition</option>
                                    <option v-for="field in fields" :value="field.id">@{{ field.name }}</option>
                                </select>
                                <input v-if="condition_field !== ''" type="text" placeholder="Condition (Use 'True' or 'False' for toggle fields)" class="form-control">
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button v-on:click="create" type="button" class="btn btn-default btn-success">Create</button>
                        </div>
                    </div>
                </div>
            </div>
        </table>

    @endif

    <p>
        <a href="#" class="btn btn-lg btn-success" data-toggle="modal" data-target="#creation">
            <span>Create new</span>
        </a>
    </p>

</div>