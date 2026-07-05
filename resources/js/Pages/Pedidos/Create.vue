<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    planes: Array,
});

const form = useForm({
    cliente: '',
    telefono: '',
    plan_id: props.planes[0]?.id ?? '',
    estado: 'nuevo',
    fecha: '',
});

function guardar() {
    form.post(route('pedidos.store'));
}
</script>

<template>
    <Head title="Nueva inscripción" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Nueva inscripción</h1>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="guardar" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="cliente" value="Nombre del cliente" />
                    <TextInput id="cliente" v-model="form.cliente" type="text" class="mt-1 block w-full" required autofocus />
                    <InputError :message="form.errors.cliente" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="telefono" value="Teléfono (WhatsApp)" />
                    <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" required placeholder="5215512345678" />
                    <InputError :message="form.errors.telefono" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="plan_id" value="Membresía" />
                    <select id="plan_id" v-model="form.plan_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                        <option v-for="plan in planes" :key="plan.id" :value="plan.id">{{ plan.nombre }}</option>
                    </select>
                    <InputError :message="form.errors.plan_id" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="fecha" value="Fecha de inicio (opcional)" />
                    <TextInput id="fecha" v-model="form.fecha" type="date" class="mt-1 block w-full" />
                    <InputError :message="form.errors.fecha" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="estado" value="Estado" />
                    <select id="estado" v-model="form.estado" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500">
                        <option value="nuevo">Nuevo</option>
                        <option value="pendiente_pago">Pendiente de anticipo</option>
                        <option value="confirmado">Confirmado</option>
                        <option value="cancelado">Cancelado</option>
                    </select>
                    <InputError :message="form.errors.estado" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Guardar inscripción</PrimaryButton>
                    <Link :href="route('pedidos.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
