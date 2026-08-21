<style>
    .cantidad{
        display: flex;
        justify-content: space-between;
        width: 99px !important;
        height: 38px;
        padding: 0px 16px 0px 16px;
        border-radius: 76px;
        border: 1px solid #D9D9D9;
        background: #FFF;
        align-items: center;
    }
    .cantidad div{
        height: auto !important;
    }
    .cantidad svg{
        cursor: pointer;
    }
</style>

<div class='monitor d-flex justify-content-center fila' style='align-items:center;'>
    <div class='cantidad'>
        <div class='cantidad-contador{{$producto->id}}' style='width:auto;'>{{number_format($item->qty, 0, ',','.')}}</div>
        <div class='d-flex flex-column justify-content-center'>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="10" viewBox="0 0 12 10" fill="none" style='margin-bottom:4px;' onclick="carrito_sumar('{{$item->rowId}}', 1)">
                <g clip-path="url(#clip0_469_4229)">
                    <path d="M6.43329 3.20831L11.1963 10.0833C11.2402 10.1467 11.2633 10.2185 11.2633 10.2916C11.2633 10.3648 11.2402 10.4366 11.1963 10.5C11.1524 10.5633 11.0893 10.6159 11.0133 10.6525C10.9373 10.6891 10.8511 10.7083 10.7633 10.7083H1.23729C1.14952 10.7083 1.0633 10.6891 0.987296 10.6525C0.911289 10.6159 0.848171 10.5633 0.804289 10.5C0.760406 10.4366 0.737304 10.3648 0.737305 10.2916C0.737305 10.2185 0.760408 10.1467 0.804292 10.0833L5.56729 3.20831C5.61118 3.14498 5.6743 3.09238 5.7503 3.05582C5.82631 3.01925 5.91253 3 6.00029 3C6.08806 3 6.17427 3.01925 6.25028 3.05582C6.32629 3.09238 6.38941 3.14498 6.43329 3.20831Z" fill="#939393"/>
                </g>
                <defs>
                    <clipPath id="clip0_469_4229">
                    <rect width="12" height="10" fill="white"/>
                    </clipPath>
                </defs>
            </svg>
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="10" viewBox="0 0 12 10" fill="none"  onclick="carrito_restar('{{$item->rowId}}', 1)">
                <g clip-path="url(#clip0_469_4227)">
                    <path d="M5.56671 8.74994L0.803708 1.87494C0.759825 1.8116 0.736722 1.73975 0.736721 1.66661C0.736721 1.59347 0.759823 1.52162 0.803706 1.45827C0.847589 1.39493 0.910705 1.34233 0.986713 1.30576C1.06272 1.26919 1.14894 1.24994 1.23671 1.24994L10.7627 1.24994C10.8505 1.24994 10.9367 1.26919 11.0127 1.30576C11.0887 1.34233 11.1518 1.39493 11.1957 1.45827C11.2396 1.52162 11.2627 1.59347 11.2627 1.6666C11.2627 1.73974 11.2396 1.8116 11.1957 1.87494L6.43271 8.74994C6.38882 8.81327 6.3257 8.86587 6.2497 8.90244C6.17369 8.939 6.08747 8.95825 5.99971 8.95825C5.91194 8.95825 5.82573 8.939 5.74972 8.90244C5.67371 8.86587 5.61059 8.81327 5.56671 8.74994Z" fill="#939393"/>
                </g>
                <defs>
                    <clipPath id="clip0_469_4227">
                    <rect width="12" height="10" fill="white" transform="translate(12 10) rotate(180)"/>
                    </clipPath>
                </defs>
            </svg>
        </div>
    </div>
</div>