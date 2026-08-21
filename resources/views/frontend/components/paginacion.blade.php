@if(count($productos) > 0)
<style>
    #links .hidden{
        opacity:1;
        display:flex;
        justify-content:space-between;
        margin-top:20px;
    }
    #links .hidden div span span:not(:last-child):not(:first-child){
        display:table-cell;

    }
 

    #links .hidden div{
        width:100%;
        text-align:end;

    }
    #links nav{
        height:41px;
    }
    #links svg{
        max-height:20px;
    }
    #links nav div:first-of-type {
        display:none;
    }
</style>
<div id='links' class='col-12 links'>
    {{$productos->appends(request()->query())->links()}}
</div>
@endif
<script>
    $(document).ready(function() {
        document.querySelector("span[aria-current='page'] span").classList.remove('bg-white')
        document.querySelector("span[aria-current='page'] span").style.backgroundColor = 'rgba(63, 129, 176, 0.5)'
    })
</script>