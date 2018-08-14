@extends('app')

@section('content')
    <div>
        <admin-fields></admin-fields>
    </div>
@endsection

@section('title')
    User #{{$this_user->id}} (Admin)
@stop

@section('page_scripts')

    <script>
        var fields = {!! $fields !!}
        var types = {!! json_encode($field_types) !!}
        var _token = "{{ csrf_token()  }}"
        Vue.component('admin-fields', {
            template: `
				@include('admin.partials.fields')
                `,
            props: {
            },
            data() {
                return {
                    fields: fields,
                    field_name: '',
                    field_type: 'Text',
                    new_field_option: '',
                    new_field_options: [],
                    field_required: false,
                    available_types: types,
                    condition_field: null,
                    condition: '',
                    errors: {
                        name: '',
                    }
                }
            },
            methods: {
                addOption: function () {
                    this.new_field_options.push(this.new_field_option);
                    this.new_field_option = '';
                },
                create: function () {
                    this.$http.post('fields', {
                        _token: _token,
                        name: this.field_name,
                        type: this.field_type,
                        required: this.field_required,
                        options: this.new_field_options,
                        condition_field: this.condition_field,
                        condition: this.condition,
                    }).then(response => {
                        window.location.reload();
                    }, response => {
                        let errors = response.body.errors;
                        console.log(errors)
                        if(errors.name) {
                            this.errors.name = errors.name[0];
                        }
                    });
                },
                deleteField: function(field_id) {
                    this.$http.delete('fields/'+field_id, {
                        body: {
                            _token: _token
                        }
                    }).then(response => {
                        for(let i=0; i<this.fields.length;++i) {
                            if(this.fields[i].id === field_id) {
                                this.fields.splice(i, 1);
                            }
                        }
                    }, response => {
                    });
                }
            },
            computed: {
            }
        });
    </script>

@endsection