<?php
/** @var array<string,mixed> $settings */
if (! defined('ABSPATH')) { exit; }
?>
<div class="aicore-chatbot-widget" data-chatbot-id="<?php echo esc_attr((string) ($settings['id'] ?? 'default')); ?>">
    <button type="button" class="aicore-chatbot-toggle"><?php esc_html_e('Ask AI', 'aicore-wp'); ?></button>
</div>
