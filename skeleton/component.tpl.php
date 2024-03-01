<twig:<?php echo $template; ?><?php foreach ($args as $name => $value) { ?> <?php echo $name . '="' . $value . '"'; } ?>>
<?php if (isset($content)) { echo '    '.$content; } ?>  
<?php foreach ($blocks as $name => $value) { ?>
    {% block <?= $name ?> %}
        <?= $value ?>  
    {% endblock %}
<?php } ?>
</twig:<?php echo $template; ?>>
