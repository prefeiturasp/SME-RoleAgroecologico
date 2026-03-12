<?php
/*
 * Template Name: Layout Login
 * Description: Modelo para Login no CoreSSO
 */

get_header('login');

?>
<div class="container-fluid container-forms">
    <div class="container">
        <div class="row">
            <div class="col-12 col-md-5 offset-md-7">
                <?php
                    get_template_part('src/Views/template-parts/login-form');
                ?>
            </div>
        </div>
    </div>			
</div>

<?php
get_footer('login');