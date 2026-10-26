import React, { createContext, useContext, useEffect, useMemo, useState } from 'react';
import { getSupabaseClient } from '../lib/supabase.js';

const AuthContext = createContext(null);

function buildLocalUser(email) {
    return {
        id: `local-${email}`,
        email,
        user_metadata: { provider: 'local' },
    };
}

export function AuthProvider({ children }) {
    const client = getSupabaseClient();
    const [user, setUser] = useState(null);
    const [session, setSession] = useState(null);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        if (!client) {
            setLoading(false);
            return undefined;
        }

        let active = true;

        client.auth
            .getSession()
            .then(({ data }) => {
                if (!active) {
                    return;
                }
                setSession(data.session ?? null);
                setUser(data.session?.user ?? null);
                setLoading(false);
            })
            .catch(() => {
                if (active) {
                    setLoading(false);
                }
            });

        const { data } = client.auth.onAuthStateChange((_event, nextSession) => {
            setSession(nextSession ?? null);
            setUser(nextSession?.user ?? null);
            setLoading(false);
        });

        return () => {
            active = false;
            data.subscription.unsubscribe();
        };
    }, [client]);

    const login = async ({ email, password }) => {
        if (client) {
            const { data, error } = await client.auth.signInWithPassword({ email, password });
            if (error) {
                throw error;
            }

            const loggedInUser = data.session?.user ?? null;

            if (loggedInUser && loggedInUser.id) {
                const appId = window.APP_ID || null;
                if (appId) {
                    const { data: existing } = await client
                        .from('app_users')
                        .select('id')
                        .eq('auth_user_id', loggedInUser.id)
                        .eq('app_id', appId)
                        .maybeSingle();

                    if (!existing) {
                        await client.from('app_users').upsert({
                            auth_user_id: loggedInUser.id,
                            app_id: appId,
                            email: loggedInUser.email || email,
                        }, { onConflict: 'auth_user_id,app_id' });
                    }
                }
            }

            setSession(data.session ?? null);
            setUser(loggedInUser);
            return loggedInUser;
        }

        const localUser = buildLocalUser(email);
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const loginWithApple = async () => {
        if (client) {
            const { error } = await client.auth.signInWithOAuth({
                provider: 'apple',
                options: {
                    redirectTo: window.location.origin,
                },
            });
            if (error) throw error;
            return null;
        }
        const localUser = buildLocalUser('apple-user@local.dev');
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const loginWithGoogle = async () => {
        if (client) {
            const { error } = await client.auth.signInWithOAuth({
                provider: 'google',
                options: {
                    redirectTo: window.location.origin,
                },
            });
            if (error) throw error;
            return null;
        }
        const localUser = buildLocalUser('google-user@local.dev');
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const signup = async ({ email, password }) => {
        if (client) {
            const { data, error } = await client.auth.signUp({ email, password });
            if (error) {
                throw error;
            }

            const newUser = data.user ?? data.session?.user ?? null;

            if (newUser && newUser.id) {
                const appId = window.APP_ID || null;
                if (appId) {
                    await client.from('app_users').upsert({
                        auth_user_id: newUser.id,
                        app_id: appId,
                        email: newUser.email || email,
                    }, { onConflict: 'auth_user_id,app_id' });
                }
            }

            setSession(data.session ?? null);
            setUser(newUser);
            return newUser;
        }

        const localUser = buildLocalUser(email);
        setSession({ user: localUser });
        setUser(localUser);
        return localUser;
    };

    const logout = async () => {
        if (client) {
            await client.auth.signOut();
        }

        setSession(null);
        setUser(null);
    };

    const value = useMemo(
        () => ({
            user,
            session,
            loading,
            mode: client ? 'supabase' : 'local',
            login,
            loginWithApple,
            loginWithGoogle,
            signup,
            logout,
        }),
        [user, session, loading, client]
    );

    return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth() {
    const context = useContext(AuthContext);

    if (!context) {
        throw new Error('useAuth must be used within AuthProvider');
    }

    return context;
}
