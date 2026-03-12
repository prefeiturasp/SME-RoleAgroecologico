<!DOCTYPE html>
<?php 

$urlBase = get_template_directory_uri() . '/src/Views/';
?>
<html lang="pt-br">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Rolê Agroecológico - Login</title>
  <link href="<?= VIEWS_PATH . '/assets/lib/bootstrap/css/bootstrap.min.css'?>" rel="stylesheet"/>
  <link rel="stylesheet" href="<?= $urlBase ?>assets/css/style-login.css"/>
  <link href="https://fonts.googleapis.com/css2?family=Roboto&display=swap" rel="stylesheet"/>
</head>
<body class="page-login">
  <div class="container-fluid sem-margem">

    <section class="banner">

      <div class="container">
        <div class="row">
          <div class="col col-md-2 col-sm-2 align-self-start"><img src="<?= $urlBase ?>assets/img/logo-role-fit.png" alt="Rolê Agroecológico" class="logo-role"></div>
          <div class="col col-md-10 col-sm-10">
              <div class="row">
                  <div class="col">

                    <nav class="navbar navbar-expand-lg navbar-light">
                      <div class="collapse navbar-collapse" id="navbarText">
                        <ul class="navbar-nav mr-auto">
                          <li class="nav-item active">
                            <a class="nav-link" href="#"><img style="width: 200px;" src="<?= $urlBase ?>assets/img/Logo_Educacao.png" alt="Prefeitura de São Paulo"></a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" href="#"><img style="width: 200px;" src="<?= $urlBase ?>assets/img/logoNiahub.png" alt="NIAHUB"></a>
                          </li>
                          <li class="nav-item">
                            <a class="nav-link" href="#"><img style="width: 200px;" src="<?= $urlBase ?>assets/img/logoCREN.png" alt="CREL"></a>
                          </li>
                        </ul>
                        <span class="navbar-text">
                          <a href="<?= site_url(); ?>" class="btn-voltar text-white">← Voltar</a>
                        </span>
                      </div>
                    </nav>
              
                  </div>
              </div>
          </div>
        </div>
      </div>
    </section>

