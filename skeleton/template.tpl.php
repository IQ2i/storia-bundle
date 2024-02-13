{% include '<?php echo $template; ?>' with {
<?php foreach ($args as $name => $value) { ?>
    <?php echo $name; ?>: '<?php echo $value; ?>',
<?php } ?>
} only %}