<style>
    .carrito-btn{
        display: flex;
height: 39px;
padding: 8px 20px;
align-items: center;
gap: 10px;
flex-shrink: 0;
border-radius: 10px;
border: 1px solid #0098DA;
background: #FFF;
    }
    .carrito-btn:hover{
        background-color: #0098DA !important;
        color: #FFF ;
        
    }
    .carrito-btn:hover svg path{
        fill: white !important;
    }
</style>

<button class='carrito-btn' onclick="carrito_remover('{{$item->rowId}}', '{{$seccion}}')">
    <svg xmlns="http://www.w3.org/2000/svg" width="19" height="19" viewBox="0 0 19 19" fill="none">
        <path d="M4.15625 4.15625L4.89844 16.0313C4.93369 16.7174 5.43281 17.2188 6.08594 17.2188H12.9141C13.5698 17.2188 14.0596 16.7174 14.1016 16.0313L14.8438 4.15625" stroke="#0098DA" stroke-width="1.1875" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M2.96875 4.15625H16.0313H2.96875Z" fill="#0098DA"/>
        <path d="M2.96875 4.15625H16.0313" stroke="#0098DA" stroke-width="1.1875" stroke-miterlimit="10" stroke-linecap="round"/>
        <path d="M7.125 4.15625V2.67188C7.12466 2.55482 7.14746 2.43886 7.1921 2.33065C7.23673 2.22244 7.30232 2.12412 7.38509 2.04135C7.46786 1.95858 7.56618 1.89299 7.67439 1.84835C7.7826 1.80371 7.89857 1.78091 8.01562 1.78125H10.9844C11.1014 1.78091 11.2174 1.80371 11.3256 1.84835C11.4338 1.89299 11.5321 1.95858 11.6149 2.04135C11.6977 2.12412 11.7633 2.22244 11.8079 2.33065C11.8525 2.43886 11.8753 2.55482 11.875 2.67188V4.15625M9.5 6.53125V14.8438M6.82812 6.53125L7.125 14.8438M12.1719 6.53125L11.875 14.8438" stroke="#0098DA" stroke-width="1.1875" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
</button>

