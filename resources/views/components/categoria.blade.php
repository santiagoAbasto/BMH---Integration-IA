<a href="{{route('productos', ['categoria' => $categoria->id])}}" class="service-item" data-aos="fade-up">
    <div class="card service-item-cont position-relative align-items-center" style=" overflow: hidden; border: none !important; border-radius: 0px">
    {{-- <img src="{{asset('imagenes/'.$categoria->portada)}}" class="card-img-top"> --}}
    <div class='categoria-img' style='width:100%;background-image:url("{{asset('imagenes/'.$categoria->portada)}}");background-position:center;background-repeat:no-repeat;background-size:cover;'>
        <div class="gradient-overlay"></div>

    </div>
    <div class="overlayCat">
<h5 class="category-name">{{ mb_convert_case($categoria->nombre, MB_CASE_TITLE, "UTF-8") }}</h5>
        {{-- <div class="svg-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="70" height="70" viewBox="0 0 70 70" fill="none">
                <circle cx="35" cy="35" r="35" fill="#236644"/>
                <path d="M35.5907 34.4093H42V35.6203H35.5907V42H34.3797V35.6203H28V34.4093H34.3797V28H35.5907V34.4093Z" fill="white"/>
              </svg>
        </div> --}}
    </div>
    
    </div>
</a>