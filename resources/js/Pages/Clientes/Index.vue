<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2';

const props = defineProps({
    clientes: Array,
    filtros: Object,
});

const buscar = ref(props.filtros?.buscar ?? '');

function buscarClientes() {
    router.get(route('clientes.index'), { buscar: buscar.value }, { preserveState: true, replace: true });
}

async function eliminar(cliente) {
    const result = await Swal.fire({
        title: `¿Eliminar a "${cliente.nombre ?? cliente.telefono}"?`,
        text: 'Esta acción no se puede deshacer.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#dc2626',
    });

    if (result.isConfirmed) {
        router.delete(route('clientes.destroy', cliente.id));
    }
}
</script>

<template>
    <Head title="Miembros" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Miembros</h1>
        </template>

        <div class="mx-auto max-w-6xl space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <input
                    v-model="buscar"
                    @keyup.enter="buscarClientes"
                    type="text"
                    placeholder="Buscar por nombre o teléfono..."
                    class="w-full max-w-xs rounded-lg border-slate-300 text-sm shadow-sm focus:border-red-500 focus:ring-red-500"
                />
                <Link
                    :href="route('clientes.create')"
                    class="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-[#dc2626] to-[#f97316] px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-red-500/20 transition hover:opacity-90"
                >
                    + Nuevo miembro
                </Link>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Teléfono</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Inscripciones</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="cliente in clientes" :key="cliente.id" class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">{{ cliente.nombre ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ cliente.telefono }}</td>
                            <td class="px-6 py-4 text-sm text-slate-500">{{ cliente.pedidos_count ?? 0 }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <Link :href="route('clientes.edit', cliente.id)" class="font-semibold text-[#dc2626] hover:text-[#f97316]">Editar</Link>
                                <button @click="eliminar(cliente)" class="ml-4 font-semibold text-slate-400 hover:text-red-600">Eliminar</button>
                            </td>
                        </tr>
                        <tr v-if="clientes.length === 0">
                            <td colspan="4" class="px-6 py-10 text-center text-sm text-slate-400">No hay miembros registrados todavía.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
