<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import TextInput from '@/Components/TextInput.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    cliente: Object,
});

const form = useForm({
    nombre: props.cliente.nombre ?? '',
    telefono: props.cliente.telefono,
});

function guardar() {
    form.put(route('clientes.update', props.cliente.id));
}
</script>

<template>
    <Head title="Editar miembro" />

    <AuthenticatedLayout>
        <template #header>
            <h1 class="text-xl font-bold text-slate-800">Editar miembro</h1>
        </template>

        <div class="mx-auto max-w-2xl">
            <form @submit.prevent="guardar" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
                <div>
                    <InputLabel for="nombre" value="Nombre" />
                    <TextInput id="nombre" v-model="form.nombre" type="text" class="mt-1 block w-full" autofocus />
                    <InputError :message="form.errors.nombre" class="mt-2" />
                </div>

                <div>
                    <InputLabel for="telefono" value="Teléfono (WhatsApp)" />
                    <TextInput id="telefono" v-model="form.telefono" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.telefono" class="mt-2" />
                </div>

                <div class="flex items-center gap-4">
                    <PrimaryButton :disabled="form.processing">Guardar cambios</PrimaryButton>
                    <Link :href="route('clientes.index')"><SecondaryButton type="button">Cancelar</SecondaryButton></Link>
                </div>
            </form>
        </div>
    </AuthenticatedLayout>
</template>
