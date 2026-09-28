<?php

echo 'Versión de PHP: ' . PHP_VERSION . '<br>';
echo 'PDO: ' . (extension_loaded('pdo') ? 'Activo' : 'No activo') . '<br>';
echo 'PDO MySQL: ' . (extension_loaded('pdo_mysql') ? 'Activo' : 'No activo') . '<br>';
echo 'mbstring: ' . (extension_loaded('mbstring') ? 'Activo' : 'No activo');