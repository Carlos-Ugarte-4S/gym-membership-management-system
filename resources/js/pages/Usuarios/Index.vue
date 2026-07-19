<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

// Recibe la información desde UsuarioController de Laravel
const props = defineProps({
  usuarios: Array,
  turnos: Array // Catálogo de turnos para el select
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Gestión Usuarios', href: '/admin/usuarios' },
];

const modalAbierto = ref(false);
const editando = ref(false);
const idUsuarioSeleccionado = ref(null);

// Formulario reactivo que hereda e unifica Persona y Usuario
const form = useForm({
  ci: '',
  nombre: '',
  apellido: '',
  telefono: '',
  direccion: '',
  sexo: 'M',
  email: '',
  login: '',
  contrasenia: '',
  rol: 'Recepcionista',
  id_turno: '',
  fecha_contrato: new Date().toISOString().split('T')[0],
  estado: true
});

const abrirModalFormulario = (usuario = null) => {
  if (usuario) {
    editando.value = true;
    idUsuarioSeleccionado.value = usuario.id_usuario;
    form.ci = usuario.persona.ci;
    form.nombre = usuario.persona.nombre;
    form.apellido = usuario.persona.apellido;
    form.telefono = usuario.persona.telefono;
    form.direccion = usuario.persona.direccion;
    form.sexo = usuario.persona.sexo;
    form.email = usuario.email;
    form.login = usuario.login;
    form.contrasenia = ''; // En blanco por seguridad
    form.rol = usuario.rol;
    form.id_turno = usuario.id_turno;
    form.fecha_contrato = usuario.fecha_contrato;
    form.estado = usuario.estado === 1 || usuario.estado === true;
  } else {
    editando.value = false;
    form.reset();
    form.id_turno = props.turnos.length > 0 ? props.turnos[0].id_turno : '';
  }
  modalAbierto.value = true;
};

const cerrarModal = () => {
  modalAbierto.value = false;
  form.reset();
};

const guardarFormulario = () => {
  if (editando.value) {
    form.put(`/admin/usuarios/${idUsuarioSeleccionado.value}`, {
      onSuccess: () => cerrarModal()
    });
  } else {
    form.post('/admin/usuarios', {
      onSuccess: () => cerrarModal()
    });
  }
};
</script>
<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 m-4 hover:shadow-lg transition-shadow duration-300">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-zinc-800/80">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-800 dark:text-zinc-100 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
          </svg>
          <span>Gestionar Usuarios del Sistema (CU-07)</span>
        </h1>
        <button 
          @click="abrirModalFormulario()" 
          class="bg-orange-600 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
        >
          + Registrar Nuevo Usuario
        </button>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-zinc-900 rounded-lg border border-gray-100 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-left">
          <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3.5">CI / Login</th>
              <th class="px-6 py-3.5">Nombre Completo</th>
              <th class="px-6 py-3.5">Correo Electrónico</th>
              <th class="px-6 py-3.5">Rol</th>
              <th class="px-6 py-3.5">Turno Asignado</th>
              <th class="px-6 py-3.5 text-center">Estado (RN-11)</th>
              <th class="px-6 py-3.5 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-gray-650 dark:text-zinc-300">
            <tr v-for="usr in usuarios" :key="usr.id_usuario" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
              <td class="px-6 py-4">
                <span class="font-bold text-gray-900 dark:text-zinc-100 block">{{ usr.persona.ci }}</span>
                <span class="text-xs text-gray-400 dark:text-zinc-500">User: {{ usr.login }}</span>
              </td>
              <td class="px-6 py-4 font-semibold text-gray-850 dark:text-zinc-200">{{ usr.persona.nombre }} {{ usr.persona.apellido }}</td>
              <td class="px-6 py-4 text-gray-500 dark:text-zinc-400">{{ usr.email }}</td>
              <td class="px-6 py-4">
                <span 
                  :class="usr.rol === 'Administrador' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                >
                  {{ usr.rol }}
                </span>
              </td>
              <td class="px-6 py-4 text-gray-500 dark:text-zinc-400 font-medium">
                {{ usr.turno ? usr.turno.nombre_turno : 'Sin turno asignado' }}
              </td>
              <td class="px-6 py-4 text-center">
                <span 
                  :class="usr.estado ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                >
                  {{ usr.estado ? 'Activo' : 'Inactivo' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <button 
                  @click="abrirModalFormulario(usr)" 
                  class="text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-355 font-bold text-xs uppercase tracking-wider transition-colors"
                >
                  Editar / Suspender
                </button>
              </td>
            </tr>
            <tr v-if="!usuarios || usuarios.length === 0">
              <td colspan="7" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">No hay personal registrado en el sistema.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div v-if="modalAbierto" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl shadow-xl w-full max-w-2xl p-6 overflow-y-auto max-h-[90vh] transform transition-all duration-300">
          <h2 class="text-xl font-black text-gray-800 dark:text-zinc-100 mb-4 pb-2 border-b border-gray-100 dark:border-zinc-800/80">
            {{ editando ? 'Modificar Usuario y Permisos' : 'Registrar Nuevo Empleado' }}
          </h2>
          
          <form @submit.prevent="guardarFormulario" class="text-gray-900 dark:text-zinc-100">
            
            <h3 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-widest mb-4 mt-2 block">1. Datos Personales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Cédula de Identidad (CI) *</label>
                <input v-model="form.ci" type="text" required :disabled="editando" class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200 disabled:bg-gray-100 dark:disabled:bg-zinc-800/50 disabled:opacity-75"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Sexo *</label>
                <select v-model="form.sexo" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200">
                  <option value="M">Masculino</option>
                  <option value="F">Femenino</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Nombres *</label>
                <input v-model="form.nombre" type="text" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Apellidos *</label>
                <input v-model="form.apellido" type="text" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Teléfono de Contacto</label>
                <input v-model="form.telefono" type="text" class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Dirección Domiciliaria</label>
                <input v-model="form.direccion" type="text" class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
            </div>

            <h3 class="text-xs font-black uppercase text-orange-600 dark:text-orange-500 tracking-widest mb-4 mt-2 block">2. Cuenta y Credenciales</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Nombre de Usuario (Login) *</label>
                <input v-model="form.login" type="text" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Correo Electrónico *</label>
                <input v-model="form.email" type="email" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">
                  Contraseña {{ editando ? '(Dejar en blanco para no cambiar)' : '*' }}
                </label>
                <input v-model="form.contrasenia" type="password" :required="!editando" class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200" placeholder="••••••••"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Fecha de Contrato *</label>
                <input v-model="form.fecha_contrato" type="date" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Rol del Sistema *</label>
                <select v-model="form.rol" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200">
                  <option value="Recepcionista">Recepcionista (Solo Operaciones)</option>
                  <option value="Administrador">Administrador (Control Total)</option>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Turno Laboral Asignado *</label>
                <select v-model="form.id_turno" required class="block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200">
                  <option value="" disabled>-- Seleccione un Turno (CU-09) --</option>
                  <option v-for="t in turnos" :key="t.id_turno" :value="t.id_turno">
                    {{ t.nombre_turno }} ({{ t.hora_inicio }} - {{ t.hora_fin }})
                  </option>
                </select>
              </div>

              <div v-if="editando" class="md:col-span-2 bg-amber-50 dark:bg-amber-950/10 p-4 rounded-lg border border-amber-200/50 dark:border-amber-900/20 mt-2">
                <label class="flex items-center space-x-3 cursor-pointer">
                  <input type="checkbox" v-model="form.estado" class="rounded text-orange-600 dark:text-orange-500 focus:ring-orange-500 h-4 w-4 bg-white dark:bg-zinc-800 border-gray-200 dark:border-zinc-800" />
                  <div>
                    <span class="text-sm font-bold text-amber-800 dark:text-amber-300">Usuario Activo en el Sistema</span>
                    <p class="text-xs text-amber-600 dark:text-amber-400/80 mt-0.5">Si desmarca esta casilla, el empleado perderá el acceso inmediato al software (RN-11).</p>
                  </div>
                </label>
              </div>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="text-sm text-red-655 dark:text-red-400 font-semibold bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 p-4 rounded-lg mb-4">
              <ul class="list-disc pl-4 space-y-1">
                <li v-for="(err, index) in form.errors" :key="index">{{ err }}</li>
              </ul>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-zinc-800">
              <button 
                type="button" 
                @click="cerrarModal" 
                class="px-5 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-gray-655 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm font-semibold transition-all duration-200"
              >
                Cancelar
              </button>
              <button 
                type="submit" 
                :disabled="form.processing"
                class="px-5 py-2.5 bg-orange-600 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm disabled:opacity-50"
              >
                {{ editando ? 'Guardar Cambios' : 'Registrar Empleado' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>