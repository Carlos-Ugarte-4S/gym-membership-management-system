<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

defineProps({
  promociones: Array
});

const breadcrumbs = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Promociones', href: '/admin/promociones' },
];

const modalAbierto = ref(false);
const editando = ref(false);
const idPromoSeleccionada = ref(null);

const form = useForm({
  nombre_promocion: '',
  porcentaje_descuento: '',
  descripcion: '',
  fecha_inicio: new Date().toISOString().split('T')[0],
  fecha_fin: ''
});

const abrirModalFormulario = (promo = null) => {
  if (promo) {
    editando.value = true;
    idPromoSeleccionada.value = promo.id_promocion;
    form.nombre_promocion = promo.nombre_promocion;
    form.porcentaje_descuento = promo.porcentaje_descuento;
    form.descripcion = promo.descripcion;
    form.fecha_inicio = promo.fecha_inicio;
    form.fecha_fin = promo.fecha_fin;
  } else {
    editando.value = false;
    idPromoSeleccionada.value = null;
    form.reset();
  }
  modalAbierto.value = true;
};

const guardarPromocion = () => {
  if (editando.value) {
    form.put(`/admin/promociones/${idPromoSeleccionada.value}`, {
      onSuccess: () => {
        modalAbierto.value = false;
        form.reset();
      }
    });
  } else {
    form.post('/admin/promociones', {
      onSuccess: () => {
        modalAbierto.value = false;
        form.reset();
      }
    });
  }
};

const cambiarEstadoPromo = (promo) => {
  const accion = promo.estado ? 'desactivar' : 'activar';
  if (confirm(`¿Está seguro de que desea ${accion} la promoción "${promo.nombre_promocion}"?`)) {
    router.patch(`/admin/promociones/${promo.id_promocion}/estado`, {}, {
      preserveScroll: true
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
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M6 20h12a2 2 0 002-2V8a2 2 0 00-2-2H6a2 2 0 00-2 2v10a2 2 0 002 2z" />
          </svg>
          <span>Campañas y Promociones (CU-10)</span>
        </h1>
        <button 
          @click="abrirModalFormulario()" 
          class="bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white text-sm font-bold py-2.5 px-4 rounded-lg shadow-md hover:shadow-lg transition-all duration-200"
        >
          + Crear Nueva Promoción
        </button>
      </div>

      <div class="overflow-x-auto bg-white dark:bg-zinc-900 rounded-lg border border-gray-100 dark:border-zinc-800">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-zinc-800 text-left">
          <thead class="bg-gray-50 dark:bg-zinc-800 text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider">
            <tr>
              <th class="px-6 py-3.5">Promoción</th>
              <th class="px-6 py-3.5">Descuento</th>
              <th class="px-6 py-3.5">Fecha Inicio</th>
              <th class="px-6 py-3.5">Fecha Límite</th>
              <th class="px-6 py-3.5 text-center">Estado</th>
              <th class="px-6 py-3.5 text-center">Acciones</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200 dark:divide-zinc-800 text-sm text-gray-650 dark:text-zinc-300">
            <tr v-for="promo in promociones" :key="promo.id_promocion" class="hover:bg-gray-50 dark:hover:bg-zinc-800/30 transition-colors">
              <td class="px-6 py-4">
                <span class="font-bold text-gray-900 dark:text-zinc-100 block">{{ promo.nombre_promocion }}</span>
                <span class="text-xs text-gray-400 dark:text-zinc-500">{{ promo.descripcion || 'Sin descripción' }}</span>
              </td>
              <td class="px-6 py-4 font-black text-emerald-600 dark:text-emerald-400 text-base">{{ promo.porcentaje_descuento }}% OFF</td>
              <td class="px-6 py-4 text-gray-500 dark:text-zinc-400 font-medium">{{ promo.fecha_inicio }}</td>
              <td class="px-6 py-4 text-gray-500 dark:text-zinc-400 font-medium">{{ promo.fecha_fin }}</td>
              <td class="px-6 py-4 text-center">
                <span 
                  :class="promo.estado ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' : 'bg-zinc-100 text-zinc-800 dark:bg-zinc-800/60 dark:text-zinc-400'"
                  class="px-2.5 py-0.5 rounded-full text-xs font-bold"
                >
                  {{ promo.estado ? 'Activa' : 'Inactiva' }}
                </span>
              </td>
              <td class="px-6 py-4 text-center space-x-3">
                <button 
                  @click="abrirModalFormulario(promo)" 
                  class="text-orange-500 hover:text-orange-600 dark:text-orange-400 dark:hover:text-orange-355 font-bold text-xs uppercase tracking-wider transition-colors"
                >
                  Editar
                </button>
                <button 
                  @click="cambiarEstadoPromo(promo)"
                  :class="promo.estado ? 'text-red-500 hover:text-red-650 dark:text-red-405 dark:hover:text-red-305' : 'text-emerald-500 hover:text-emerald-605 dark:text-emerald-405 dark:hover:text-emerald-305'"
                  class="font-bold text-xs uppercase tracking-wider transition-colors"
                >
                  {{ promo.estado ? 'Desactivar' : 'Activar' }}
                </button>
              </td>
            </tr>
            <tr v-if="!promociones || promociones.length === 0">
              <td colspan="6" class="px-6 py-8 text-center text-gray-400 dark:text-zinc-500 italic">No hay descuentos vigentes actualmente.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Modal -->
      <div v-if="modalAbierto" class="fixed inset-0 bg-black/70 backdrop-blur-md flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-900 border border-gray-100 dark:border-zinc-800 rounded-xl shadow-xl w-full max-w-md p-6 transform transition-all duration-300">
          <h2 class="text-xl font-black text-gray-800 dark:text-zinc-100 mb-4 pb-2 border-b border-gray-100 dark:border-zinc-800/80">
            {{ editando ? 'Modificar Promoción' : 'Configurar Descuento Temporal' }}
          </h2>
          
          <form @submit.prevent="guardarPromocion" class="space-y-4">
            <div class="space-y-4">
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Nombre de la Promoción *</label>
                <input v-model="form.nombre_promocion" type="text" required placeholder="Ej. Black Friday" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Porcentaje de Descuento (%) *</label>
                <input v-model="form.porcentaje_descuento" type="number" min="1" max="100" required placeholder="15" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
              </div>
              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Desde *</label>
                  <input v-model="form.fecha_inicio" type="date" required class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
                </div>
                <div>
                  <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Hasta *</label>
                  <input v-model="form.fecha_fin" type="date" required class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"/>
                </div>
              </div>
              <div>
                <label class="block text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">Descripción Corta</label>
                <textarea v-model="form.descripcion" rows="2" class="mt-1.5 block w-full border border-gray-200 dark:border-zinc-800 rounded-lg p-2.5 text-sm bg-white dark:bg-zinc-800 text-gray-900 dark:text-zinc-100 focus:ring-2 focus:ring-orange-500 focus:outline-none transition-all duration-200"></textarea>
              </div>
            </div>

            <div v-if="Object.keys(form.errors).length > 0" class="mt-4 text-sm text-red-650 dark:text-red-400 font-semibold bg-red-50 dark:bg-red-950/20 border border-red-200/50 dark:border-red-900/30 p-3 rounded-lg">
              <ul class="list-disc pl-4 space-y-1">
                <li v-for="(err, idx) in form.errors" :key="idx">{{ err }}</li>
              </ul>
            </div>

            <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-zinc-800/80">
              <button type="button" @click="modalAbierto = false" class="px-5 py-2.5 border border-gray-200 dark:border-zinc-800 rounded-lg text-gray-650 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-800 text-sm font-semibold transition-all duration-200">Cancelar</button>
              <button type="submit" :disabled="form.processing" class="px-5 py-2.5 bg-gradient-to-r from-orange-500 to-red-600 hover:from-orange-600 hover:to-red-700 text-white font-bold rounded-lg shadow-md hover:shadow-lg transition-all duration-200 text-sm disabled:opacity-50">
                {{ editando ? 'Guardar Cambios' : 'Lanzar Promoción' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </AppLayout>
</template>