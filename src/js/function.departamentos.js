let departamentosDynamicTable = null;
let rolesDynamicTable = null;

document.addEventListener('DOMContentLoaded', function () {
    console.log('Cargando gestión de departamentos y roles...');
    
    // Inicializar tablas dinámicas
    initTablasDepartamentos();
    
    // Configurar formularios
    setupFormularios();
});

function initTablasDepartamentos() {
    console.log('Inicializando tablas de departamentos y roles...');
    
    // Inicializar tabla de departamentos
    setTimeout(() => {
        if (typeof initDepartamentosDynamicTable !== 'undefined') {
            departamentosDynamicTable = initDepartamentosDynamicTable();
            console.log('Tabla de departamentos inicializada:', departamentosDynamicTable);
            setupDepartamentosEventDelegation();
        } else {
            console.error('initDepartamentosDynamicTable no está disponible. Verificar carga de DataTableRefactor.js');
        }
    }, 100);
    
    // Inicializar tabla de roles
    setTimeout(() => {
        if (typeof initRolesDynamicTable !== 'undefined') {
            rolesDynamicTable = initRolesDynamicTable();
            console.log('Tabla de roles inicializada:', rolesDynamicTable);
            setupRolesEventDelegation();
        } else {
            console.error('initRolesDynamicTable no está disponible. Verificar carga de DataTableRefactor.js');
        }
    }, 150);
}

function setupFormularios() {
    // NUEVO DEPARTAMENTO
    const formDepto = document.querySelector("#formDepto");
    if (formDepto) {
        formDepto.onsubmit = async function (e) {
            e.preventDefault();

            const nombre = document.querySelector('#txtNombreDepto').value;
            if (nombre.trim() === '') {
                notifi("El nombre es obligatorio.", "warning");
                return;
            }

            try {
                const formData = new FormData(formDepto);
                const url = base_url + 'User/setDepartamento';
                const response = await fetch(url, {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                if (data.success) {
                    $('#modalFormDepto').modal('hide');
                    formDepto.reset();
                    notifi(data.message, "success");
                    recargarTablaDepartamentos();
                } else {
                    notifi(data.message, "error");
                }
            } catch (error) {
                console.error('Error:', error);
                notifi("Ocurrió un error en la operación.", "error");
            }
        };
    }

    // FORMULARIO DE ROLES
    const formRol = document.querySelector("#formRol");
    if (formRol) {
        formRol.onsubmit = async function (e) {
            e.preventDefault();
            const nombre = document.querySelector('#txtNombreRol').value;
            if (nombre.trim() === '') {
                notifi("El nombre del rol es obligatorio.", "warning");
                return;
            }
            try {
                const formData = new FormData(formRol);
                const url = base_url + 'User/setRol';
                const response = await fetch(url, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    closeRolModal();
                    formRol.reset();
                    notifi(data.message, "success");
                    recargarTablaRoles();
                } else {
                    notifi(data.message, "error");
                }
            } catch (error) {
                console.error('Error:', error);
                notifi("Ocurrió un error en la operación.", "error");
            }
        };
    }
}

function setupDepartamentosEventDelegation() {
    // Delegación de eventos para botones de departamentos
    $(document).on('click', '.btn-edit-depto', function () {
        const deptoId = $(this).data('id');
        fntEditDepto(deptoId);
    });

    $(document).on('click', '.btn-delete-depto', function () {
        const deptoId = $(this).data('id');
        fntDelDepto(deptoId);
    });
}

function setupRolesEventDelegation() {
    // Delegación de eventos para botones de roles
    $(document).on('click', '.btn-edit-rol', function () {
        const rolId = $(this).data('id');
        fntEditRol(rolId);
    });

    $(document).on('click', '.btn-delete-rol', function () {
        const rolId = $(this).data('id');
        fntDelRol(rolId);
    });
}

function recargarTablaDepartamentos() {
    if (departamentosDynamicTable) {
        departamentosDynamicTable.reload();
    }
}

function recargarTablaRoles() {
    if (rolesDynamicTable) {
        rolesDynamicTable.reload();
    }
}

// --- FUNCIONES PARA DEPARTAMENTOS ---
function openModal() {
    document.querySelector('#idDepartamento').value = "";
    document.querySelector('#titleModal').innerHTML = "<i class='fas fa-plus-circle mr-2'></i> Nuevo Departamento";
    document.querySelector('#btnActionTextDepto').innerHTML = "<i class='fas fa-save mr-2'></i> Guardar";
    document.querySelector("#formDepto").reset();
    $('#modalFormDepto').modal('show');
}

function openRolModal() {
    document.querySelector('#idRol').value = "";
    document.querySelector('#titleModalRol').innerHTML = "<i class='fas fa-plus-circle mr-2'></i> Nuevo Rol";
    document.querySelector('#btnActionTextRol').innerHTML = "<i class='fas fa-save mr-2'></i> Guardar";
    document.querySelector("#formRol").reset();
    $('#modalFormRol').modal('show');
}

function closeRolModal() {
    $('#modalFormRol').modal('hide');
}

async function fntEditDepto(iddepto) {
    try {
        document.querySelector('#titleModal').innerHTML = "<i class='fas fa-edit mr-2'></i> Actualizar Departamento";
        document.querySelector('#btnActionTextDepto').innerHTML = "<i class='fas fa-save mr-2'></i> Actualizar";

        const url = `${base_url}User/getDepartamento/${iddepto}`;
        const response = await fetch(url);
        const result = await response.json();

        if (result.success) {
            const depto = result.data;
            document.querySelector("#idDepartamento").value = depto.departamento_id;
            document.querySelector("#txtNombreDepto").value = depto.departamento_nombre;
            document.querySelector("#txtDescripcionDepto").value = depto.departamento_descripcion;
            document.querySelector("#listStatusDepto").value = depto.departamento_status;
            $('#modalFormDepto').modal('show');
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error:', error);
        notifi("Ocurrió un error al obtener los datos.", "error");
    }
}

// --- FUNCIONES PARA ROLES ---
async function fntEditRol(idrol) {
    document.querySelector('#titleModalRol').innerHTML = "<i class='fas fa-edit mr-2'></i> Actualizar Rol";
    document.querySelector('#btnActionTextRol').innerHTML = "<i class='fas fa-save mr-2'></i> Actualizar";
    try {
        const url = `${base_url}User/getRol/${idrol}`;
        const response = await fetch(url);
        const result = await response.json();
        if (result.success) {
            const rol = result.data;
            document.querySelector("#idRol").value = rol.rol_id;
            document.querySelector("#txtNombreRol").value = rol.rol_nombre;
            document.querySelector("#txtDescripcionRol").value = rol.rol_descripcion;
            document.querySelector("#listStatusRol").value = rol.rol_status;
            $('#modalFormRol').modal('show');
        } else {
            notifi(result.message, "error");
        }
    } catch (error) {
        console.error('Error:', error);
        notifi("Ocurrió un error al obtener los datos del rol.", "error");
    }
}

function fntDelDepto(iddepto) {
    Swal.fire({
        title: 'Eliminar Departamento',
        text: "¿Realmente quieres eliminar este departamento?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('idDepartamento', iddepto);
                const url = base_url + 'User/delDepartamento';
                const response = await fetch(url, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    notifi(data.message, "success");
                    recargarTablaDepartamentos();
                } else {
                    notifi(data.message, "error");
                }
            } catch (error) {
                notifi("Ocurrió un error en la operación.", "error");
            }
        }
    });
}

function fntDelRol(idrol) {
    Swal.fire({
        title: 'Eliminar Rol',
        text: "¿Realmente quieres eliminar este rol?",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'No, cancelar'
    }).then(async (result) => {
        if (result.isConfirmed) {
            try {
                const formData = new FormData();
                formData.append('idRol', idrol);
                const url = base_url + 'User/delRol';
                const response = await fetch(url, { method: 'POST', body: formData });
                const data = await response.json();
                if (data.success) {
                    notifi(data.message, "success");
                    recargarTablaRoles();
                } else {
                    notifi(data.message, "error");
                }
            } catch (error) {
                console.error('Error:', error);
                notifi("Ocurrió un error en la operación de eliminación.", "error");
            }
        }
    });
}