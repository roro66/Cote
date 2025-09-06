<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Gestión de Categorías de Gasto
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <h1 class="text-3xl font-bold">Categorías de Gasto</h1>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="openAddModal()">
                            <i class="fas fa-plus"></i> Agregar Categoría
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table id="categories-table" class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Descripción</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Agregar Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="categoryForm">
                        @csrf
                        <input type="hidden" id="categoryId" name="id">

                        <div class="mb-3">
                            <label for="code" class="form-label">Código (opcional)</label>
                            <input type="text" class="form-control" id="code" name="code">
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveCategory()">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        let categoriesTable;

        $(document).ready(function() {
            categoriesTable = $('#categories-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('datatables.expense-categories') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'code', name: 'code'},
                    {data: 'name', name: 'name'},
                    {data: 'description', name: 'description'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: window.COTESO_DATATABLE_LANG || {},
                dom: '<"row mb-3"<"col-sm-4"B><"col-sm-4"f><"col-sm-4"l>>rtip',
                lengthMenu: [[10,25,50,100,-1],[10,25,50,100,'Todos']],
                pageLength: 10
            });
        });

        function openAddModal() {
            $('#categoryModalLabel').text('Agregar Categoría');
            $('#categoryForm')[0].reset();
            $('#categoryId').val('');
            $('.form-control').removeClass('is-invalid');
            $('#categoryModal').modal('show');
        }

        function editCategory(id) {
            $.get("{{ route('expense-categories.index') }}/" + id, function(data) {
                $('#categoryModalLabel').text('Editar Categoría');
                $('#categoryId').val(data.id);
                $('#code').val(data.code);
                $('#name').val(data.name);
                $('#description').val(data.description);
                $('.form-control').removeClass('is-invalid');
                $('#categoryModal').modal('show');
            });
        }

        function saveCategory() {
            const id = $('#categoryId').val();
            const url = id ? "{{ route('expense-categories.index') }}/" + id : "{{ route('expense-categories.store') }}";
            const method = id ? 'PUT' : 'POST';

            $.ajax({
                url: url,
                type: method,
                data: $('#categoryForm').serialize(),
                success: function(response) {
                    $('#categoryModal').modal('hide');
                    categoriesTable.ajax.reload();
                    toastr.success('Categoría guardada exitosamente');
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        $('.form-control').removeClass('is-invalid');
                        $('.invalid-feedback').text('');

                        for (let field in errors) {
                            $('#' + field).addClass('is-invalid');
                            $('#' + field).next('.invalid-feedback').text(errors[field][0]);
                        }
                    } else {
                        toastr.error('Error al guardar la categoría');
                    }
                }
            });
        }

        function deleteCategory(id, name) {
            if (confirm('¿Estás seguro de que quieres eliminar la categoría "' + name + '"?')) {
                $.ajax({
                    url: "{{ route('expense-categories.index') }}/" + id,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function() {
                        categoriesTable.ajax.reload();
                        toastr.success('Categoría eliminada exitosamente');
                    },
                    error: function(xhr) {
                        if (xhr.status === 400) {
                            toastr.error(xhr.responseJSON.message);
                        } else {
                            toastr.error('Error al eliminar la categoría');
                        }
                    }
                });
            }
        }
    </script>
    @endpush

</x-app-layout>
