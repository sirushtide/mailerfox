import { createClient } from '@supabase/supabase-js';

let client = null;

export function getSupabaseClient() {
    const url = window.APP_SUPABASE_URL;
    const key = window.APP_SUPABASE_PUBLISHABLE_KEY;

    if (!url || !key) {
        return null;
    }

    if (!client) {
        client = createClient(url, key);
    }

    return client;
}
