<a href="{{route('novedad', ['id' => $novedad->id])}}" class="novedades-item"  data-aos="fade-up">
    <div class='d-flex justify-content-center novedades-item-cont' style='height:312px;background-image: url("imagenes/{{$novedad->portada}}"); background-size: cover; background-position: center;'>
      <div class="gradient-overlay-novedad"></div>
      <div class="svg-container">
        <svg xmlns="http://www.w3.org/2000/svg" width="39" height="37" viewBox="0 0 39 37" fill="none">
          <rect width="39" height="37" fill="#FCFCFC" fill-opacity="0.8"/>
          <path d="M29.6781 26.8921L24.9175 22.1315C26.0637 20.6057 26.6824 18.7485 26.6803 16.8402C26.6803 11.9657 22.7146 8 17.8402 8C12.9657 8 9 11.9657 9 16.8402C9 21.7146 12.9657 25.6803 17.8402 25.6803C19.7485 25.6824 21.6057 25.0637 23.1315 23.9175L27.8921 28.6781C28.1331 28.8936 28.4474 29.0085 28.7705 28.9995C29.0936 28.9905 29.401 28.8581 29.6295 28.6295C29.8581 28.401 29.9905 28.0936 29.9995 27.7705C30.0085 27.4474 29.8936 27.1331 29.6781 26.8921ZM11.5258 16.8402C11.5258 15.5913 11.8961 14.3705 12.5899 13.3321C13.2838 12.2937 14.2699 11.4843 15.4237 11.0064C16.5775 10.5285 17.8472 10.4034 19.072 10.6471C20.2969 10.8907 21.422 11.4921 22.3051 12.3752C23.1882 13.2583 23.7896 14.3834 24.0332 15.6083C24.2769 16.8332 24.1518 18.1028 23.6739 19.2566C23.196 20.4104 22.3867 21.3966 21.3483 22.0904C20.3099 22.7842 19.089 23.1546 17.8402 23.1546C16.1661 23.1526 14.5612 22.4866 13.3774 21.3029C12.1937 20.1192 11.5278 18.5142 11.5258 16.8402Z" fill="#6B6B6B"/>
      </svg>
      </div>
    </div>
    <div class='d-flex flex-column justify-content-start novedades-info'>
      <div class='encabezado h-100 d-flex flex-column justify-content-between'>
        <div>
          <p class='etiqueta'>{{$novedad->etiqueta}}</p>  
          </div>
          <div>
            
            <h3 class='novedades-titulo'>{{$novedad->titulo}}</h3>
        </div>
        <div>
          <p class='epigrafe'>{{$novedad->epigrafe}}</p>

        </div>

        {{-- <div class='w-100 '>
          <div class='d-flex justify-content-end mb-3' >
            <p class='leer-mas'>Ver más</p>
            <div>
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path class="flecha" d="M12 4L10.59 5.41L16.17 11H4V13H16.17L10.59 18.59L12 20L20 12L12 4Z" fill="#236644"/>
              </svg>
            </div>
            
          </div>
          
        </div> --}}
      </div>
      
      
    </div>
    
</a>