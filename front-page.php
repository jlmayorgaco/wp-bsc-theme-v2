<?php

  get_header();

?>

<?php require_once get_template_directory() . '/components/products/card.php'; ?>
<?php require_once get_template_directory() . '/components/products/slider.php'; ?>
<?php $home_options = get_option('bsc_home_favorites', []); ?>

<main class="bsc bsc__page page-home">

    <section class="section home__swiper--section">
        <?php require_once get_template_directory() . '/components/swiper.php'; ?>
    </section>

    <section class="section home__products--section">
          <div class="section__container">
              <h1 class="bsc__title">
                <strong>Últimos</strong> Lanzamientos
              </h1>
              <?php 
                $key = 'ultimos_lanzamientos';
                $options = $home_options;
                $skus = explode(',', $options[$key]);
                $slider = new BSC_Products_Sliders();
                $slider->setSkus($skus);
                $slider->setSlug($key);
                $slider->render();
              ?>
          </div>
          <div class="section__container">
              <h1 class="bsc__title"><strong>Favoritos</strong> en BSC</h1>

              <div class="bsc__tabs">
                <div class="tabs__header tabs__headers--favs">
                  <div class="tab__header active" data-tab-name="piel-seca">
                    <svg class="tab__icon" xmlns="http://www.w3.org/2000/svg" id="Capa_1" data-name="Capa 1" viewBox="0 0 100 79"><defs><style>      .cls-1 {        fill: #333;      }      .cls-1, .cls-2 {        stroke-width: 0px;      }      .cls-2 {        fill: #333;      }      .cls-3 {        fill: none;        stroke: #333;        stroke-dasharray: 0 0 1 8;        stroke-linecap: round;        stroke-linejoin: round;        stroke-width: 2px;      }    </style></defs><line class="cls-3" x1="90.95" y1="68.38" x2="9.05" y2="68.38"></line><path class="cls-2" d="M50,54.75c-4.27,0-8.33-1.67-11.42-4.7-3.08-3.08-4.78-7.14-4.78-11.44,0-6.23,7.59-17.38,12.13-24.04,1.19-1.72,2.08-3.02,2.83-4.25.24-.36.57-.58.94-.67l.32-.04c.27,0,.53.08.76.23.15.1.33.27.44.44l1.04,1.57c.57.85,1.15,1.74,1.79,2.68h0c4.55,6.68,12.14,17.83,12.14,24,0,4.3-1.69,8.36-4.77,11.43s-7.13,4.77-11.43,4.77Z"></path><path class="cls-1" d="M50.01,12.09c-.67,1.04-1.45,2.19-2.43,3.61-4.41,6.47-11.78,17.3-11.78,22.91,0,3.76,1.49,7.32,4.18,10.02,2.71,2.66,6.27,4.12,10.02,4.12s7.32-1.49,10.02-4.18,4.18-6.25,4.18-10.02c0-5.55-7.66-16.81-11.78-22.85h-.01c-.65-.97-1.24-1.86-1.81-2.73l-.58-.88Z"></path></svg>
                    <span class="tab__title">Piel Seca</span>
                  </div>
                  <div class="tab__header" data-tab-name="piel-normal">
                    <svg class="tab__icon" xmlns="http://www.w3.org/2000/svg" id="Capa_1" data-name="Capa 1" viewBox="0 0 100 79"><defs><style>      .cls-1 {        fill: #333;      }      .cls-1, .cls-2 {        stroke-width: 0px;      }      .cls-2 {        fill: #000;      }      .cls-3 {        fill: none;        stroke: #000;        stroke-dasharray: 0 0 1 8;        stroke-linecap: round;        stroke-linejoin: round;        stroke-width: 2px;      }    </style></defs><path class="cls-2" d="M48.66,55c-.59,0-1.15-.21-1.59-.6-2.1-1.84-4.02-3.47-5.87-5.05-5.11-4.36-9.47-8.07-12.53-11.71-3.53-4.2-5.17-8.14-5.17-12.41,0-3.94,1.34-7.63,3.78-10.39,2.58-2.92,6.15-4.53,10.06-4.53,5.52,0,9,3.27,10.94,6.01.13.19.26.38.39.57.13-.19.26-.38.39-.57,1.94-2.74,5.42-6.01,10.94-6.01,3.91,0,7.48,1.61,10.06,4.53,2.44,2.76,3.78,6.45,3.78,10.39,0,4.27-1.64,8.21-5.18,12.41-3.07,3.65-7.43,7.37-12.48,11.67l-.25.21c-1.79,1.53-3.64,3.11-5.61,4.83-.5.44-1.07.65-1.65.65Z"></path><path class="cls-1" d="M37.34,12.31c-3.33,0-6.37,1.37-8.56,3.86-2.11,2.4-3.28,5.62-3.28,9.07,0,3.77,1.5,7.31,4.71,11.13,2.95,3.51,7.25,7.18,12.23,11.42,1.93,1.64,3.85,3.28,5.89,5.07.23.21.37.26.61.05,2.03-1.78,3.89-3.36,5.69-4.89l.25-.21c4.98-4.25,9.29-7.91,12.25-11.43,3.21-3.82,4.71-7.35,4.71-11.13,0-3.45-1.16-6.67-3.28-9.07-2.19-2.49-5.23-3.85-8.56-3.85-4.66,0-7.64,2.81-9.31,5.17-.4.57-.79,1.19-1.14,1.85l-.88,1.64-.88-1.64c-.35-.65-.73-1.27-1.14-1.85-1.67-2.36-4.64-5.17-9.31-5.17Z"></path><line class="cls-3" x1="90.95" y1="68.38" x2="9.05" y2="68.38"></line></svg>
                    <span class="tab__title">Piel Normal</span>
                  </div>
                  <div class="tab__header" data-tab-name="piel-mixta">
                    <svg class="tab__icon" xmlns="http://www.w3.org/2000/svg" id="Capa_1" width="100" height="79" viewBox="0 0 100 79"><defs><style>.cls-1{fill:#fc0054;}.cls-1,.cls-2{stroke-width:0px;}.cls-2{fill:#333;}</style></defs><path class="cls-2" d="m38.6,59.1s0,0-.1,0c-.5,0-.9-.4-1.1-.9,0-.2-.1-.4-.1-.7,0-.5.1-.9.2-1.2.9-2.3,1.8-4.6,2.6-6.8.5-1.4,1.1-2.8,1.6-4.2,1.2-3.1,2.4-6.2,3.5-9.3l.6-1.6h-3.4c-1,0-2,0-3,0-.3,0-1.1,0-1.6-.7-.5-.7-.2-1.5,0-1.8.9-2.4,1.8-4.8,2.7-7.2l5.2-13.8c.5-1.3,1.1-1.7,2.4-1.7h10.4c.6,0,1.5,0,1.9.7.4.7,0,1.5-.3,2l-7.7,14.1h1.1c1.8,0,3.5,0,5.3,0,.3,0,.6.1.9.2,0,0,.1,0,.2,0l1,.3-.3,1c0,0,0,.1,0,.2,0,.2-.1.6-.3.9-1.9,2.8-3.8,5.6-5.7,8.4-3.3,4.8-6.7,9.8-10,14.8-1,1.6-2.1,3.2-3.1,4.8l-.4.6s-1.1,1.6-2.3,1.6Z"></path><path class="cls-1" d="m43.7,32.5h5l-1.6,4.3c-1.2,3.1-2.3,6.2-3.5,9.3-.5,1.4-1.1,2.8-1.6,4.2-.6,1.6-1.2,3.2-1.8,4.7.9-1.4,1.7-2.7,2.6-4.1,3.3-5,6.7-10,10-14.8,1.8-2.6,3.6-5.2,5.3-7.8-1.6,0-3.1,0-4.7,0h-4.3s.8-1.7.8-1.7c0-.2.2-.3.2-.5l8.1-14.7h-10.1c-.2,0-.3,0-.4,0,0,0,0,.2-.2.4l-5.2,13.8c-.9,2.3-1.7,4.6-2.6,6.9.9,0,1.8,0,2.7,0h1.3Z"></path><path class="cls-2" d="m90.9,69.4h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-.9c-.6,0-1-.4-1-1s.4-1,1-1h.9c.6,0,1,.4,1,1s-.4,1-1,1Z"></path></svg>
                    <span class="tab__title">Piel Mixta</span>
                  </div>
                  <div class="tab__header" data-tab-name="piel-grasa">
                    <svg class="tab__icon" xmlns="http://www.w3.org/2000/svg" id="Capa_1" width="100" height="79" viewBox="0 0 100 79"><defs><style>.cls-1{fill:#fc0054;}.cls-1,.cls-2{stroke-width:0px;}.cls-2{fill:#333;}</style></defs><path class="cls-2" d="m55.2,16.6l-.8.6.8-.6c-.7-1-1.3-2-1.9-2.9l-.9-1.4c-.1-.2-.3-.3-.4-.4-.2-.2-.5-.2-.8-.2h-.3c-.4.1-.7.4-.9.7-.8,1.2-1.7,2.5-2.9,4.3-.5.7-1,1.5-1.5,2.3-1-1.6-2-3.1-2.9-4.4-.4-.6-.8-1.2-1.2-1.8l-.5-.8c0-.1-.2-.3-.4-.4-.2-.1-.4-.2-.7-.2h-.3c-.3.1-.6.3-.8.6-.5.8-1,1.6-1.8,2.7-3.9,5.7-7.7,11.6-7.7,15.3s1.1,5.4,3.1,7.5c.8.8,1.7,1.4,2.6,1.9-.1.7-.2,1.3-.2,1.8,0,4.4,1.7,8.5,4.8,11.6,3.1,3.1,7.2,4.8,11.6,4.8s8.5-1.7,11.6-4.8c3.1-3.1,4.8-7.2,4.8-11.6,0-6.3-7.7-17.6-12.3-24.3Z"></path><path class="cls-1" d="m31.1,29.8c0-3.4,4.8-10.4,7.3-14.2.5-.7.9-1.3,1.2-1.8.4.6.8,1.2,1.2,1.8,1,1.4,2.2,3.2,3.3,5.1v.2c-.1,0,.2.5.2.5,2.5,4,3.7,6.9,3.8,8.6,0,2.2-.9,4.2-2.5,5.8-1.6,1.6-3.8,2.5-6,2.5s-2.3-.2-3.4-.7c-1-.4-1.9-1-2.7-1.8-1.6-1.6-2.5-3.8-2.5-6Z"></path><path class="cls-1" d="m61.3,51c-2.7,2.7-6.4,4.2-10.2,4.2s-7.4-1.5-10.2-4.2c-2.7-2.7-4.2-6.3-4.2-10.2s0-.4,0-.7c1,.3,2,.4,3,.4,2.8,0,5.4-1.1,7.4-3.1,2-2,3.1-4.6,3.1-7.4s0-.1,0-.2c0,0,0-.1,0-.2,0-2-1.2-4.9-3.6-9.1.7-1.1,1.4-2.1,2-3,1-1.5,1.8-2.6,2.5-3.7l.5.7c.6.9,1.2,1.9,2,2.9h0c4.2,6.1,11.9,17.5,11.9,23.2s-1.5,7.4-4.2,10.2Z"></path><path class="cls-2" d="m90.9,69.4h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-.9c-.6,0-1-.4-1-1s.4-1,1-1h.9c.6,0,1,.4,1,1s-.4,1-1,1Z"></path></svg>
                    <span class="tab__title">Piel Grasa</span>
                  </div>
                  <div class="tab__header" data-tab-name="hair-care">
                    <svg class="tab__icon" xmlns="http://www.w3.org/2000/svg" id="Capa_1" width="100" height="79" viewBox="0 0 100 79"><defs><style>.cls-1{fill:#fc0054;}.cls-1,.cls-2{stroke-width:0px;}.cls-2{fill:#333;}</style></defs><path class="cls-2" d="m38.6,59.1s0,0-.1,0c-.5,0-.9-.4-1.1-.9,0-.2-.1-.4-.1-.7,0-.5.1-.9.2-1.2.9-2.3,1.8-4.6,2.6-6.8.5-1.4,1.1-2.8,1.6-4.2,1.2-3.1,2.4-6.2,3.5-9.3l.6-1.6h-3.4c-1,0-2,0-3,0-.3,0-1.1,0-1.6-.7-.5-.7-.2-1.5,0-1.8.9-2.4,1.8-4.8,2.7-7.2l5.2-13.8c.5-1.3,1.1-1.7,2.4-1.7h10.4c.6,0,1.5,0,1.9.7.4.7,0,1.5-.3,2l-7.7,14.1h1.1c1.8,0,3.5,0,5.3,0,.3,0,.6.1.9.2,0,0,.1,0,.2,0l1,.3-.3,1c0,0,0,.1,0,.2,0,.2-.1.6-.3.9-1.9,2.8-3.8,5.6-5.7,8.4-3.3,4.8-6.7,9.8-10,14.8-1,1.6-2.1,3.2-3.1,4.8l-.4.6s-1.1,1.6-2.3,1.6Z"></path><path class="cls-1" d="m43.7,32.5h5l-1.6,4.3c-1.2,3.1-2.3,6.2-3.5,9.3-.5,1.4-1.1,2.8-1.6,4.2-.6,1.6-1.2,3.2-1.8,4.7.9-1.4,1.7-2.7,2.6-4.1,3.3-5,6.7-10,10-14.8,1.8-2.6,3.6-5.2,5.3-7.8-1.6,0-3.1,0-4.7,0h-4.3s.8-1.7.8-1.7c0-.2.2-.3.2-.5l8.1-14.7h-10.1c-.2,0-.3,0-.4,0,0,0,0,.2-.2.4l-5.2,13.8c-.9,2.3-1.7,4.6-2.6,6.9.9,0,1.8,0,2.7,0h1.3Z"></path><path class="cls-2" d="m90.9,69.4h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-.9c-.6,0-1-.4-1-1s.4-1,1-1h.9c.6,0,1,.4,1,1s-.4,1-1,1Z"></path></svg>
                    <span class="tab__title">Hair Care</span>
                  </div>
                  <div class="tab__header" data-tab-name="maquillaje">
                    <svg class="tab__icon" xmlns="http://www.w3.org/2000/svg" id="Capa_1" width="100" height="79" viewBox="0 0 100 79"><defs><style>.cls-1{fill:#fc0054;}.cls-1,.cls-2{stroke-width:0px;}.cls-2{fill:#333;}</style></defs><path class="cls-2" d="m90.9,69.4h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-1c-.6,0-1-.4-1-1s.4-1,1-1h1c.6,0,1,.4,1,1s-.4,1-1,1Zm-9,0h-.9c-.6,0-1-.4-1-1s.4-1,1-1h.9c.6,0,1,.4,1,1s-.4,1-1,1Z"></path><path class="cls-2" d="m59,12.9c-1.7-4.7-5.8-7.7-10.7-7.8,0,0,0,0-.1,0-4.9,0-9.1,2.9-10.9,7.7-1.2,3.2-.7,6.7,1.4,10.6,0,.2.4,1,.7,1.6,1.2,2.9,1.7,4.2,2.2,4.6v25.2c0,2.8,2.3,5,5,5h2.7c2.8,0,5-2.3,5-5v-26s2.3-5,2.3-5c1.3-2.8,3.5-7.6,2.3-11Z"></path><path class="cls-1" d="m47.6,29.9s0,0,0,0h5v6.3h-9.2v-6.2h4.2Z"></path><path class="cls-1" d="m49.4,58.1h-2.7c-1.8,0-3.2-1.4-3.2-3.2v-16.9h5v7.3c0,.5.4.9.9.9s.9-.4.9-.9v-7.3h2.4v16.9c0,1.8-1.4,3.2-3.2,3.2Z"></path><path class="cls-1" d="m52.8,28.1h-.5s1.1-6.7,1.1-6.7c0-.5-.3-1.1-.8-1.1-.6,0-1.1.3-1.1.8l-1.1,7h-1.5v-6.9c0-.6-.4-1-1-1s-1,.4-1,1v6.8h-1.5s-1.1-7-1.1-7c0-.5-.6-.9-1.1-.8-.5,0-.9.6-.8,1.1l1.1,6.7h-.6c-.4-.7-1.2-2.7-1.6-3.8-.4-1-.6-1.6-.8-1.8-1.9-3.4-2.3-6.4-1.3-9.1,1.8-4.8,5.9-6.5,9.2-6.5h.1c3.3,0,7.4,1.8,9.1,6.6,1,2.7-1.1,7.2-2.3,9.7l-2.3,5Z"></path></svg>
                    <span class="tab__title">Maquillaje Coreano</span>
                  </div>
                </div>

                <div class="tabs__content">
                  <div class="tab__content active" data-tab-name="piel-seca">
                    <?php 
                    $key = 'piel_seca';
                    $options = $home_options;
                    $skus = explode(',', $options[$key]);
                    $slider = new BSC_Products_Sliders();
                    $slider->setSkus($skus);
                    $slider->setSlug($key);
                    $slider->render();
                  ?>
                  </div>
                  <div class="tab__content" data-tab-name="piel-normal">
                    <?php 
                      $key = 'piel_normal';
                      $options = $home_options;
                      $skus = explode(',', $options[$key]);
                      $slider = new BSC_Products_Sliders();
                      $slider->setSkus($skus);
                      $slider->setSlug($key);
                      $slider->render();
                    ?>
                  </div>
                  <div class="tab__content" data-tab-name="piel-mixta">
                      <?php 
                      $key = 'piel_mixta';
                      $options = $home_options;
                      $skus = explode(',', $options[$key]);
                      $slider = new BSC_Products_Sliders();
                      $slider->setSkus($skus);
                      $slider->setSlug($key);
                      $slider->render();
                    ?>
                  </div>
                  <div class="tab__content" data-tab-name="piel-grasa">
                    <?php 
                      $key = 'piel_grasa';
                      $options = $home_options;
                      $skus = explode(',', $options[$key]);
                      $slider = new BSC_Products_Sliders();
                      $slider->setSkus($skus);
                      $slider->setSlug($key);
                      $slider->render();
                    ?>
                  </div>
                  <div class="tab__content" data-tab-name="hair-care">
                    <?php 
                      $key = 'hair_care';
                      $options = $home_options;
                      $skus = explode(',', $options[$key]);
                      $slider = new BSC_Products_Sliders();
                      $slider->setSkus($skus);
                      $slider->setSlug($key);
                      $slider->render(); 
                    ?>
                  </div>
                  <div class="tab__content" data-tab-name="maquillaje">
                    <?php 
                      $key = 'maquillaje';
                      $options = $home_options;
                      $skus = explode(',', $options[$key]);
                      $slider = new BSC_Products_Sliders();
                      $slider->setSkus($skus);
                      $slider->setSlug($key);
                      $slider->render();
                    ?>
                  </div>
                </div>
              </div>

              <!--
              <a class="bsc__button bsc__button--outline bsc__button--floating" href="<?php echo esc_url( bsc_get_shop_url() ); ?>">¡Ver todos!</a>
              -->

        </div>
    </section>

  <?php
    // Category showcase (6 blocks)
    $groups = [
        [
            'slug'  => 'group-skin-care',
            'title' => 'SKIN CARE',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/1PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días !'
        ],
        [
            'slug'  => 'group-hair-care',
            'title' => 'HAIR CARE',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/2PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días !'
        ],
        [
            'slug'  => 'group-make-up',
            'title' => 'MAKE UP',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/3PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días !'

        ]
        /*,
        [
            'slug'  => 'dispositivos',
            'title' => 'DISPOSITIVOS',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/4PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días !'

        ],
        [
            'slug'  => 'inner-beauty',
            'title' => 'INNER BEAUTY',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/5PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días !'

        ],
        [
            'slug'  => 'spa-kbeauty',
            'title' => 'SPA KBEAUTY',
            'image' => esc_url( get_stylesheet_directory_uri() ) . '/images/shop/6PAG_INTERNAR_IMAGENES_WEB.jpg',
            'text'  => 'Tu rutina <strong>coreana empieza aquí</strong>: limpiadores, esencias, serums, contornos, mascarillas y más para una piel saludable todos los días !'

        ],
        */
    ];
    ?>

    <section class="section bsc-kb-grid bsc-kb-grid--home">
      <?php foreach ( $groups as $g ) :
        $base = trailingslashit( home_url( '/product-category/' ) );
        $term_link = $base .$g['slug'] . '/';
      ?>
        <article class="bsc-kb-card">
          <a href="<?php echo esc_url( $term_link ); ?>" class="bsc-kb-card__link">
            <div class="bsc-kb-card__imgwrap">
        
                <div class="bsc-kb-card__back">
                    <img src="<?php echo esc_url( $g['image'] ); ?>" alt="<?php echo esc_attr( $g['title'] ); ?>" loading="lazy">
                </div> 

                <div class="bsc-kb-card__front">
                    <img class="bsc-kb-card__icon" src="<?php echo esc_url( get_template_directory_uri() ); ?>/images/shop/hear_icon.png" alt="" width="25px">
                    <p class="bsc-kb-text">
                        <?php echo wp_kses($g['text'], ['strong' => []]); ?>
                    </p>
                </div>
            </div>
            <div class="bsc-kb-card__label">
              <?php echo esc_html( $g['title'] ); ?>
            </div>
          </a>
        </article>
      <?php endforeach; ?>
    </section>

    <section class="section home__brands--section">
      <div class="section__back">
        <img class="brands__bg" alt="" src="<?php echo get_template_directory_uri();?>/images/home_brands/bsc_home_brands_bg.jpg">
      </div>
      <div class="section__front">
      <div class="section__container">
        <img class="brands__title" alt="" src="<?php echo get_template_directory_uri();?>/images/home_brands/bsc_home_brands_text.png">
          <ul class="brands__items">
        <?php
        $brands = [
          [
            'name' => 'COSRX',
            'slug' => 'sk-marca-cosrx'
          ],
          [
            'name' => 'SOME BY MI',
            'slug' => 'sk-marca-some-by-mi'
          ],
          [
            'name' => 'Heimish',
            'slug' => 'sk-marca-heimish'
          ],
          [
            'name' => 'Pyunkang Yul',
            'slug' => 'sk-marca-pyunkang-yul'
          ],
          [
            'name' => 'Im From',
            'slug' => 'sk-marca-im-from'
          ],
          [
            'name' => 'Beauty of Joseon',
            'slug' => 'sk-marca-beauty-of-joseon'
          ],
          [
            'name' => 'TOCOBO',
            'slug' => 'sk-marca-tocobo'
          ],
          [
            'name' => 'BANILA CO',
            'slug' => 'sk-marca-banila-co'
          ],
          [
            'name' => 'Benton',
            'slug' => 'sk-marca-benton'
          ]
        ];

        foreach ($brands as $i => $brand) {
          $raw_slug    = $brand['slug'];
          $name        = esc_attr($brand['name']);
          $slug        = esc_attr($raw_slug);
          $image_index = $i + 1;
          $image_url   = get_template_directory_uri() . "/images/home_brands/brand_0{$image_index}.png";

          // BSC-006: usar get_term_link() para generar la URL jerárquica correcta.
          // El URL plano /product-category/{slug} tenía depth=1 y caía en renderLevel2(),
          // que solo maneja grupos (group-*). Las marcas necesitan depth>=2 â†’ renderLevel3().
          $term      = get_term_by('slug', $raw_slug, 'product_cat');
          $term_link = ($term && !is_wp_error($term)) ? get_term_link($term) : null;
          $brand_url = ($term_link && !is_wp_error($term_link)) ? esc_url($term_link) : '#';

          echo <<<HTML
          <li class="brands__item" data-brand-name="{$name}" data-brand-slug="{$slug}">
            <a href="{$brand_url}">
              <img src="{$image_url}" alt="Marca {$name}">
            </a>
          </li>
          HTML;
        }
        ?>
        </ul>
      </div>
      </div>
    </section>

    <section class="section home__featured-products">
      <div class="section__container">
              <h1 class="bsc__title">
                <strong>Productos</strong> Destacados
              </h1>
              <?php 
                $slider = new BSC_Products_Sliders();
                $slider->render();
              ?>
      </div>
    </section>

    <section class="section home__about--section">
      <div class="section__back">
        <img class="about__bg" src="<?php echo get_template_directory_uri();?>/images/bsc_home_about_bg.jpg" alt="">
      </div>
      <div class="section__front">
        <div class="front__container">

          <div class="about__title">
            <strong>Bubble</strong> Lover ! Conoce más de nosotros
          </div>

          <div class="about__cols">

            <div class="about__col">
              <img class="about__icon" src="<?php echo get_template_directory_uri();?>/images/bsc_home_about_icon1.png" alt="">
              <h1 class="about__title">Envíos gratis</h1>
              <h2 class="about__subtitle">Por compras mayores a $300.000</h2>
            </div>

            <div class="about__col-separator"></div>

            <div class="about__col">
              <img class="about__icon" src="<?php echo get_template_directory_uri();?>/images/bsc_home_about_icon2.png" alt="">
              <h1 class="about__title">Sumas puntos en BSC</h1>
              <h2 class="about__subtitle">con cada compra que hagas</h2>
            </div>

            <div class="about__col-separator"></div>

            <div class="about__col">
              <img class="about__icon" src="<?php echo get_template_directory_uri();?>/images/bsc_home_about_icon3.png" alt="">
              <h1 class="about__title">Regalito sorpresa</h1>
              <h2 class="about__subtitle">con el programa de fidelización</h2>
            </div>

          </div>
         
      </div>
    </section>

    <section class="section home__contact--section">
      <div class="home__contact__container">
        <div class="home__contact__cols">

          <!-- Left Column -->
          <div class="home__contact__col home__contact__col--left">
            <div class="home__contact__title-row">
              <h1 class="home__contact__headline">But first skincare</h1>
              <img 
                class="home__contact__wave" 
                src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_wave.png" 
                alt="Wave"
              >
            </div>

            <!-- BSC-021: blog mockup con badge "Próximamente" -->
            <div class="home__contact__blog-row">
              <img
                class="home__contact__blog-card"
                src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_card_image1.png"
                alt="Bubble Blog"
              >
              <img
                class="home__contact__blog-photo"
                src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_card_image2.png"
                alt=""
              >
              <img
                class="home__contact__blog-sticker"
                src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_card_image3.png"
                alt=""
              >
              <div class="home__contact__blog-coming-soon">Próximamente</div>
            </div>
          </div>

          <!-- Right Column -->
          <div class="home__contact__col home__contact__col--right">
            <div class="home__contact__hearts">
              <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
              <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
              <img src="<?php echo get_template_directory_uri(); ?>/images/bsc_icon_white_heart.png" alt="">
            </div>

            <p class="home__contact__description">
              <strong>Únete a la comunidad de BSC</strong> y entérate antes que nadie de promociones, noticias y lanzamientos exclusivos
            </p>

            <form class="home__contact__form" id="bsc-newsletter-form" action="#">
              <input type="email" name="email" id="bsc-newsletter-email" placeholder="Tu e-mail" class="home__contact__input" required>
              <input type="submit" value="¡Quiero Ser Parte !" class="home__contact__submit" id="bsc-newsletter-submit">
              <p class="home__contact__feedback home__contact__feedback--error" id="bsc-newsletter-error"></p>
            </form>
            <div class="home__contact__success" id="bsc-newsletter-success">
              <p class="home__contact__success-msg" id="bsc-newsletter-success-msg"></p>
            </div>


            <img 
              class="home__contact__final-image" 
              src="<?php echo get_template_directory_uri(); ?>/images/bsc_contact_final_image.png" 
              alt="BSC Skin Care First"
            >
          </div>

        </div>
      </div>
    </section>



</main>

<?php get_footer(); ?>
