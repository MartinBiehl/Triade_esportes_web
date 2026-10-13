<?php
/** Entrada do checkout: manda para a primeira etapa que falta. */
require __DIR__ . '/../includes/bootstrap.php';
checkout_exigir('revisao');
redirecionar('checkout/revisao.php');
