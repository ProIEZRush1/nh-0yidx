<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    planes: Array,
    filtros: Object,
});

const buscar = ref(props.filtros?.buscar ?? '');

function buscarMembresias() {
    router.get(route('planes.index'), { buscar: buscar.value }, { preserveState: true, replace: true });
}

function formatearPrecio(centavos) {
    return '$' + (centavos / 100).toLocaleString('es-MX', { minimumFractionDigits: 0 }) + ' MXN/mes';
}

async function eliminar(plan) {
    const result = await Swal.fire({
        title: `¿Eliminar "${plan.nombre}"?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (result.isConfirmed) {
        router.delete(route('planes.destroy', plan.id));
    }
}
</script>

<template>
    <Head title="Membresías" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Membresías</h1>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <input
                    v-model="buscar"
                    @keyup.enter="buscarMembresias"
                    type="text"
                    placeholder="Buscar membresía..."
                    class="w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                />
                <Link
                    :href="route('planes.create')"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#dc2626] to-[#f97316] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/20 transition hover:opacity-90"
                >
                    + Nueva membresía
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Membresía</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Precio</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Estado</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Orden</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="plan in planes" :key="plan.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4">
                                <p class="text-sm font-semibold text-slate-800">{{ plan.nombre }}</p>
                                <p v-if="plan.descripcion" class="mt-0.5 max-w-md text-xs text-slate-500">{{ plan.descripcion }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-slate-700">{{ formatearPrecio(plan.precio) }}</td>
                            <td class="px-6 py-4">
                                <span
                                    :class="plan.activo ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'"
                                    class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                >
                                    {{ plan.activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ plan.orden }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('planes.edit', plan.id)" class="font-semibold text-[#dc2626] hover:text-[#f97316]">Editar</Link>
                                <button @click="eliminar(plan)" class="ml-4 font-semibold text-slate-400 hover:text-red-600">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="planes.length === 0">
                            <td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">No hay membresías registradas todavía.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
