<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    pedido: Object,
    planes: Array,
    trialLocked: Boolean,
});

const form = useForm({
    cliente: props.pedido.cliente ?? '',
    telefono: props.pedido.telefono,
    plan_id: props.pedido.plan_id,
    estado: props.pedido.estado,
    fecha: props.pedido.fecha ?? '',
    motivo: props.pedido.motivo ?? '',
});

const intentaConfirmarBloqueado = computed(() => props.trialLocked && form.estado === 'confirmado');

function guardar() {
    form.put(route('pedidos.update', props.pedido.id));
}
</script>

<template>
    <Head title="Editar inscripción" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Editar inscripción</h1>
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
                    <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" required />
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
                    <InputLabel for="fecha" value="Fecha de inicio" />
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
                    <p v-if="intentaConfirmarBloqueado" class="mt-2 text-sm text-amber-700">
                        🔒 Confirmar oficialmente la inscripción se activa al confirmar tu proyecto con el anticipo.
                    </p>
                </div>

                <div v-if="form.estado === 'cancelado'">
                    <InputLabel for="motivo" value="Motivo de cancelación" />
                    <textarea
                        id="motivo"
                        v-model="form.motivo"
                        rows="3"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                    ></textarea>
                    <InputError :message="form.errors.motivo" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Guardar cambios</PrimaryButton>
                    <Link :href="route('pedidos.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
