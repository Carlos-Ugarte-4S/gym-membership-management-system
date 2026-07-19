<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Database, Download, RefreshCw, Upload, Trash2, AlertTriangle, AlertCircle } from 'lucide-vue-next';

defineProps({
  backups: Array
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Copias de Seguridad', href: '/admin/backup' },
];

const cargandoBackup = ref(false);
const cargandoRestauracion = ref(false);
const backupParaRestaurar = ref(null);
const modalConfirmacionAbierto = ref(false);
const errorMensaje = ref('');

const formCrear = useForm({});
const formRestaurar = useForm({
  nombre_archivo: '',
  archivo_subido: null
});

const lanzarCrearBackup = () => {
  cargandoBackup.value = true;
  errorMensaje.value = '';
  formCrear.post('/admin/backup/crear', {
    preserveScroll: true,
    onSuccess: () => {
      cargandoBackup.value = false;
      alert('Respaldo manual generado con éxito.');
    },
    onError: (errors) => {
      cargandoBackup.value = false;
      errorMensaje.value = errors.error || 'Error al generar la copia de seguridad.';
    }
  });
};

const confirmarRestauracion = (backup) => {
  backupParaRestaurar.value = backup;
  formRestaurar.nombre_archivo = backup.nombre;
  formRestaurar.archivo_subido = null;
  modalConfirmacionAbierto.value = true;
};

const lanzarRestauracion = () => {
  modalConfirmacionAbierto.value = false;
  cargandoRestauracion.value = true;
  errorMensaje.value = '';
  
  formRestaurar.post('/admin/backup/restaurar', {
    preserveScroll: true,
    onSuccess: () => {
      cargandoRestauracion.value = false;
      alert('Base de datos restaurada con éxito en el sistema.');
    },
    onError: (errors) => {
      cargandoRestauracion.value = false;
      errorMensaje.value = errors.error || 'Error durante la restauración de la base de datos.';
    }
  });
};

// Carga de archivo externo (.sql o .backup)
const procesarArchivoBackup = (event) => {
  const archivo = event.target.files[0];
  if (!archivo) return;
  
  formRestaurar.nombre_archivo = '';
  formRestaurar.archivo_subido = archivo;
  
  if (confirm(`¿Está seguro de que desea subir y restaurar el archivo "${archivo.name}"? Se sobrescribirá toda la base de datos actual.`)) {
    cargandoRestauracion.value = true;
    formRestaurar.post('/admin/backup/restaurar', {
      preserveScroll: true,
      onSuccess: () => {
        cargandoRestauracion.value = false;
        alert('Base de datos restaurada con éxito.');
        event.target.value = ''; // Limpiar input
      },
      onError: (errors) => {
        cargandoRestauracion.value = false;
        errorMensaje.value = errors.error || 'Error durante la restauración de la base de datos.';
        event.target.value = '';
      }
    });
  } else {
    event.target.value = '';
  }
};
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-6 bg-white dark:bg-zinc-900 rounded-xl shadow-md border border-gray-100 dark:border-zinc-800 m-4 hover:shadow-lg transition-shadow duration-300">
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6 pb-4 border-b border-gray-100 dark:border-zinc-800/80">
        <h1 class="text-2xl font-extrabold tracking-tight text-gray-800 dark:text-zinc-100 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
          </svg>
          <span>Gestionar Copias de Seguridad (CU-11)</span>
        </h1>
        
        <button 
          @click="lanzarCrearBackup" 
          :disabled="cargandoBackup || cargandoRestauracion"
          class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200 flex items-center gap-2 disabled:opacity-50"
        >
          <Database class="h-4 w-4" />
          <span>{{ cargandoBackup ? 'Generando...' : 'Realizar Respaldo' }}</span>
        </button>
      </div>

      <!-- Alertas de Error -->
      <div v-if="errorMensaje" class="bg-red-50 dark:bg-red-950/20 border-l-4 border-red-500 dark:border-red-600 p-4 rounded-r-lg mb-6 flex items-start gap-3 text-red-750 dark:text-red-405 font-semibold">
        <AlertCircle class="h-5 w-5 flex-shrink-0 text-red-500 dark:text-red-400" />
        <div>
          <span>Error de Respaldo / Restauración:</span>
          <p class="text-sm font-normal mt-0.5">{{ errorMensaje }}</p>
        </div>
      </div>

      <!-- Alerta de Advertencia Crítica de Restauración -->
      <div class="bg-amber-50 dark:bg-amber-950/10 border-l-4 border-amber-500 dark:border-amber-600 p-4 rounded-r-lg mb-6 flex items-start gap-3 text-amber-855 dark:text-amber-400 font-bold">
        <AlertTriangle class="h-5 w-5 flex-shrink-0 text-amber-550 dark:text-amber-400" />
        <div>
          <span>Información de Seguridad Crítica (RNF-06 / RNF-07):</span>
          <p class="text-xs font-semibold leading-relaxed mt-1 text-amber-700 dark:text-amber-400/80">
            La restauración de base de datos reemplazará de forma irreversible todos los datos actuales de clientes, pagos y membresías con el estado del backup seleccionado. Asegúrese de realizar una copia de seguridad antes de proceder con cualquier cambio.
          </p>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Tabla de Backups Disponibles en Servidor -->
        <div class="lg:col-span-2 bg-white dark:bg-zinc-900 border border-gray-150 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
          <h2 class="text-base font-extrabold text-gray-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
            <Database class="h-5 w-5 text-orange-500" />
            <span>Respaldos Disponibles en el Servidor</span>
          </h2>
          
          <div class="overflow-x-auto text-gray-900 dark:text-zinc-100 rounded-lg border border-gray-100 dark:border-zinc-800">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-left">
              <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
                <tr>
                  <th class="px-4 py-3.5">Archivo</th>
                  <th class="px-4 py-3.5">Tamaño</th>
                  <th class="px-4 py-3.5">Fecha de Creación</th>
                  <th class="px-4 py-3.5 text-center">Acciones</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-gray-655 dark:text-zinc-300">
                <tr v-for="bk in backups" :key="bk.nombre" class="hover:bg-gray-50/50 dark:hover:bg-zinc-800/30 transition-colors">
                  <td class="px-4 py-3 font-semibold text-gray-900 dark:text-zinc-200 truncate max-w-[200px]" :title="bk.nombre">
                    {{ bk.nombre }}
                  </td>
                  <td class="px-4 py-3 text-gray-500 dark:text-zinc-400 font-medium">{{ bk.tamano }}</td>
                  <td class="px-4 py-3 text-gray-500 dark:text-zinc-400 font-medium">{{ bk.fecha }}</td>
                  <td class="px-4 py-3 text-center">
                    <div class="flex justify-center items-center gap-3">
                      <a 
                        :href="bk.url" 
                        download 
                        class="text-orange-500 hover:text-orange-655 dark:text-orange-400 dark:hover:text-orange-355 font-bold transition-colors p-1"
                        title="Descargar archivo de respaldo"
                      >
                        <Download class="h-4 w-4" />
                      </a>
                      <button 
                        @click="confirmarRestauracion(bk)"
                        :disabled="cargandoBackup || cargandoRestauracion"
                        class="text-emerald-500 hover:text-emerald-655 dark:text-emerald-405 dark:hover:text-emerald-305 font-bold transition-colors p-1 disabled:opacity-50 flex items-center gap-1"
                        title="Restaurar base de datos a este punto"
                      >
                        <RefreshCw class="h-4 w-4" />
                        <span class="text-xs">Restaurar</span>
                      </button>
                    </div>
                  </td>
                </tr>
                <tr v-if="!backups || backups.length === 0">
                  <td colspan="4" class="px-4 py-8 text-center text-gray-405 dark:text-zinc-500 italic">No se encontraron copias de seguridad generadas en el servidor.</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Columna Lateral: Restauración Externa -->
        <div class="bg-zinc-50 dark:bg-zinc-800/30 border border-zinc-250 dark:border-zinc-800 rounded-xl p-5 shadow-sm">
          <h2 class="text-base font-extrabold text-gray-800 dark:text-zinc-100 mb-4 flex items-center gap-2">
            <Upload class="h-5 w-5 text-orange-500" />
            <span>Restauración desde Archivo Externo</span>
          </h2>
          
          <div class="space-y-4">
            <p class="text-xs text-gray-500 dark:text-zinc-400 leading-relaxed font-semibold">
              ¿Tiene una copia de seguridad local (.sql o .backup)? Puede subirla directamente aquí para realizar la importación.
            </p>
            
            <div class="border-2 border-dashed border-gray-300 dark:border-zinc-700 rounded-xl p-6 text-center bg-white dark:bg-zinc-800 hover:border-orange-500 dark:hover:border-orange-500 transition-colors duration-200">
              <input 
                type="file" 
                @change="procesarArchivoBackup"
                accept=".sql,.backup"
                id="upload-backup"
                class="hidden"
                :disabled="cargandoBackup || cargandoRestauracion"
              />
              <label 
                for="upload-backup" 
                class="cursor-pointer flex flex-col items-center gap-2 text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-300 transition-colors"
              >
                <Upload class="h-8 w-8 text-gray-400 dark:text-zinc-500" />
                <span class="text-sm font-bold">Seleccionar archivo</span>
                <span class="text-[10px] text-gray-450 dark:text-zinc-500">Archivos .sql o .backup</span>
              </label>
            </div>
            
            <div v-if="cargandoRestauracion" class="text-xs text-orange-600 dark:text-orange-400 font-bold flex items-center gap-2 bg-orange-50 dark:bg-orange-950/20 p-3 rounded-lg border border-orange-100 dark:border-orange-900/30">
              <RefreshCw class="h-4 w-4 animate-spin text-orange-500" />
              <span>Restaurando base de datos, por favor espere...</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal de Confirmación de Restauración -->
    <div v-if="modalConfirmacionAbierto" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50">
      <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6 transform transition-all duration-300">
        <h3 class="text-lg font-black text-red-655 dark:text-red-400 mb-4 pb-2 border-b border-gray-100 dark:border-zinc-800/80 flex items-center gap-2">
          <AlertTriangle class="h-5 w-5 text-red-555 dark:text-red-400" />
          <span>Confirmar Restauración de Datos</span>
        </h3>
        
        <p class="text-sm text-gray-655 dark:text-zinc-300 mb-4 font-semibold">
          Está a punto de restaurar el respaldo: <strong class="text-gray-900 dark:text-zinc-100 font-bold block mt-1 break-all bg-gray-50 dark:bg-zinc-800 p-2.5 rounded-lg border dark:border-zinc-800">{{ backupParaRestaurar?.nombre }}</strong>
        </p>
        
        <p class="text-xs text-red-655 dark:text-red-405 bg-red-50 dark:bg-red-950/20 p-4 rounded-lg mb-6 font-semibold leading-relaxed border border-red-200/50 dark:border-red-900/30">
          ⚠️ ¡ADVERTENCIA!: Esta acción eliminará permanentemente todos los datos actuales del gimnasio y los reemplazará con los de este archivo. Esta acción no se puede deshacer.
        </p>
        
        <div class="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800/80">
          <button 
            type="button" 
            @click="modalConfirmacionAbierto = false" 
            class="px-5 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-gray-655 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm font-semibold transition-all duration-200"
          >
            Cancelar
          </button>
          <button 
            type="button" 
            @click="lanzarRestauracion" 
            class="px-5 py-2.5 bg-gradient-to-r from-red-500 to-red-700 hover:from-red-600 hover:to-red-800 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm"
          >
            Confirmar Restauración
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
