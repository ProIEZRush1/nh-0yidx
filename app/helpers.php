<?php

if (! function_exists('trial_locked')) {
    /**
     * Single source of truth for whether money-making actions are gated behind
     * the trial lock (see config/trial.php). Controlled by TRIAL_LOCKED — never
     * hardcode this check elsewhere.
     */
    function trial_locked(): bool
    {
        return (bool) config('trial.locked');
    }
}

if (! function_exists('trial_locked_message')) {
    function trial_locked_message(): string
    {
        return 'Esta función se activa al confirmar tu proyecto con el anticipo. Mientras tanto puedes ver y configurar todo. 🔒';
    }
}
