<?php

/**
 * MailPurse Addon Hooks
 *
 * A lightweight WordPress-style hook system used by addons. Addon
 * bootstrap files are required by the AddonServiceProvider after the
 * MAILPURSE_ADDON_LOADED constant is defined, so these functions are
 * always available by the time an addon registers its filters/actions.
 *
 * NOTE: This is intentionally dependency-free so it can be loaded very
 * early (during provider registration) before the framework is fully up.
 */

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $GLOBALS['mailpurse_hooks'][$tag][$priority][] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
        ];
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $tag, callable $callback, int $priority = 10): void
    {
        if (!isset($GLOBALS['mailpurse_hooks'][$tag][$priority])) {
            return;
        }

        foreach ($GLOBALS['mailpurse_hooks'][$tag][$priority] as $key => $hook) {
            if ($hook['callback'] === $callback) {
                unset($GLOBALS['mailpurse_hooks'][$tag][$priority][$key]);
            }
        }
    }
}

if (!function_exists('has_filter')) {
    function has_filter(string $tag): bool
    {
        return !empty($GLOBALS['mailpurse_hooks'][$tag]);
    }
}

if (!function_exists('apply_filters')) {
    /**
     * Apply all registered filters for a tag and return the (possibly) modified value.
     *
     * @param mixed ...$args Extra arguments passed to the callbacks after the value.
     * @return mixed
     */
    function apply_filters(string $tag, $value, ...$args)
    {
        if (empty($GLOBALS['mailpurse_hooks'][$tag])) {
            return $value;
        }

        $hooks = $GLOBALS['mailpurse_hooks'][$tag];
        ksort($hooks);

        foreach ($hooks as $callbacks) {
            foreach ($callbacks as $hook) {
                $accepted = max(1, (int) $hook['accepted_args']);
                $params = array_slice(array_merge([$value], $args), 0, $accepted);
                $value = call_user_func_array($hook['callback'], $params);
            }
        }

        return $value;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $tag, callable $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $GLOBALS['mailpurse_actions'][$tag][$priority][] = [
            'callback' => $callback,
            'accepted_args' => $acceptedArgs,
        ];
    }
}

if (!function_exists('do_action')) {
    function do_action(string $tag, ...$args): void
    {
        if (empty($GLOBALS['mailpurse_actions'][$tag])) {
            return;
        }

        $hooks = $GLOBALS['mailpurse_actions'][$tag];
        ksort($hooks);

        foreach ($hooks as $callbacks) {
            foreach ($callbacks as $hook) {
                $accepted = max(1, (int) $hook['accepted_args']);
                $params = array_slice($args, 0, $accepted);
                call_user_func_array($hook['callback'], $params);
            }
        }
    }
}
