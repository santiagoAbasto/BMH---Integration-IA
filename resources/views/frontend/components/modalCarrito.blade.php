<div class="modal fade" id="productoModal" tabindex="-1" role="dialog" aria-labelledby="productoModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document" style="max-width: 700px !important; width: 60% !important">
      <div class="modal-content" >
        <div class="modal-header d-flex justify-content-end" style="border: none">
          <button onclick="cerrarModal()" type="button" class="close" data-dismiss="modal" aria-label="Close" style="background: transparent; border:none; font-size:25px">
            <span aria-hidden="true">&times;</span>
          </button>
          
        </div>
        <div class="modal-body" style="max-width: none !important">
      
            <div class='row' style="height: 400px !important">
                <div class='col-lg-7' style='padding-bottom:48px;'>
                    <div id="productoModalImg"></div>
                </div>
                <div class='col-lg-5'>
                    <div class='d-flex flex-column justify-content-between' style="padding-top: 0px !important; height: 85%">
                        <div>
                            <div class='d-flex flex-column'>
                                <span class="producto-titulo" style="color: #0098DA !important" id="codigo"></span>
                                <span class="producto-titulo" id="productoModalLabel"></span>
                                <div id="productoModalDescripcion"></div>
                            </div>
            
                            <div class="d-flex flex-column">
                                <div class="d-flex flex-column">
                                    @for ($i = 1; $i <= 78; $i++)
                                        <div id="divColumna{{ $i }}" style="display:none;">
                                            <span class="nR" style="padding-left: 0px !important" id="productoColumna{{ $i }}" data-field="columna_{{ $i }}"></span>
                                            <span class="infoR" id="categoriaColumna{{ $i }}" data-field="columna_{{ $i }}"></span>
                                        </div>
                                    @endfor
                                </div>
                                
            
                              
                            </div>
                        </div>
            
                        <div class="row d-flex">
                            <div class="col-lg-12">
                                <a href="{{ route('contacto') }}">
                                    <button id='consultar' class='green-btn' style="width: 100% !important">Consultar</button>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
  
      </div>
    </div>
  </div>