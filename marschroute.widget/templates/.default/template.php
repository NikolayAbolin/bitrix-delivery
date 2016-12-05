<?php
// Получение Настроек Маршрута
$param_route = CSaleDeliveryHandler::GetBySID('Route')->GetNext()['CONFIG']['CONFIG'];

// Получение ID полей по Коду
$props = CSaleOrderProps::GetList(
    array(),
    array(),
    false,
    false,
    array('ID', 'CODE')
//    array()
);

$route_fields = array( 'route_cost', 'route_kladr', 'route_place' );
$loc_fields = array('LOCATION', 'ADDRESS');

$find_route_fields = array();

while ( $prop = $props->Fetch() ) {
    $code = $prop['CODE'];

    if (in_array( $code, $route_fields) ) {
        $find_route_fields[$code] = $prop['ID'];
        continue;
    }

    if (in_array( $code, $loc_fields) ) {

        if (!array_key_exists( $code, $find_route_fields )) {
            $find_route_fields[$code] = array($prop['ID']);
            continue;
        }
        array_push($find_route_fields[$code], $prop['ID']);
    }
}
/////////////////////////////////////////////////////////////////////////
?>



<!--***************************************-->
<style>

    .routewidget_window>div{position:relative}
    .routewidget_window_close{
        background: rgb(245, 245, 245) none repeat scroll 0 0;
        border: 2px solid rgb(204, 204, 204);
        border-radius: 30px;
        color: rgb(101, 101, 101);
        content: "f";
        cursor: pointer;
        font-family: sans-serif;
        font-size: 24px;
        font-weight: bold;
        height: 27px;
        line-height: 28px;
        position: absolute;
        right: -15px;
        text-align: center;
        top: -15px;
        width: 27px;
    }


</style>

<div id="routewidget_window" class="routewidget_window" style="
    position: fixed;
    top: 0;
    right: 0;
    bottom: 0;
    left: 0;
    z-index: 999999;
    display: none;
">
<div style="
    width: 1100px;
    margin: auto ">

    <div id="routewidget_window_close" class="routewidget_window_close">&times;</div>

    <div id="routewidget"
         style="
            /*border: 1px solid red;*/
            /*width: 1100px;*/
            /*height: 600px;*/

            width: <?php echo $param_route['width']['VALUE']?>px,
            height: <?php echo $param_route['height']['VALUE']?>px,

            /*height: 60px;*/
            min-width: 750px;
            min-height: 602px;
            margin-top: 30px;
    "></div>
</div>
</div>
<!--**************************************************************************-->

<?php // Подключение виджета ?>
<script type="text/javascript" src="https://marschroute.ru/widgets/delivery/js/widget.js"></script>

<script type="text/javascript">

// Реализация функционала по манипуляции с DOM
    function getElem(id) {
        return document.getElementById(id);
    }

    function hideParentNode(elem, level) {
        if (!level) level = 1;
        while (level) {
            elem = elem.parentNode;
            level--;
        }
        elem.style.display = 'none';
    }

    function hideElem(elem) {
        elem.style.display = 'none';
    }

    function showElem(elem) {
        elem.style.display = 'block';
    }

    function addEvent(elem, event, handler) {
        if (elem.addEventListener)
            elem.addEventListener(event, handler, false);
        else
            elem.attachEvent("on" + event,
                function (event) {
                    return handler.call(elem, event);
                });
    }

    function getElemFilter(elems, pref) {
        var len = elems.length;
        for(var i = 0; i < len; i++) {
            var ge = getElem(pref+elems[i]);
            if (ge) elems[i] = ge;
            else {
                elems.splice(i);
                len--;
            }

        }
        return elems[0];
    }

    function bindReady(handler){
    var called = false;

    function ready() { // (1)
        if (called) return
        called = true
        handler()
    }

    if ( document.addEventListener ) { // (2)
        document.addEventListener( "DOMContentLoaded", function(){
            ready()
        }, false )
    } else if ( document.attachEvent ) {  // (3)

        // (3.1)
        if ( document.documentElement.doScroll && window == window.top ) {
            function tryScroll(){
                if (called) return
                if (!document.body) return
                try {
                    document.documentElement.doScroll("left")
                    ready()
                } catch(e) {
                    setTimeout(tryScroll, 0)
                }
            }
            tryScroll()
        }

        // (3.2)
        document.attachEvent("onreadystatechange", function(){

            if ( document.readyState === "complete" ) {
                ready()
            }
        })
    }

    // (4)
    if (window.addEventListener)
        window.addEventListener('load', ready, false)
    else if (window.attachEvent)
        window.attachEvent('onload', ready)
    /*  else  // (4.1)
     window.onload=ready
     */
}
////////////////////////////////////////////////////////////////////////////////////////////

    var route_cost = "ORDER_PROP_" + <?php echo $find_route_fields['route_cost'] ?>;
    route_cost = getElem(route_cost);
    hideParentNode(route_cost, 2);

    var route_kladr = "ORDER_PROP_" + <?php echo $find_route_fields['route_kladr'] ?>;
    route_kladr = getElem(route_kladr);
    hideParentNode(route_kladr, 2);

    var route_place = "ORDER_PROP_" + <?php echo $find_route_fields['route_place'] ?>;

    route_place = getElem(route_place);
    hideParentNode(route_place, 2);

    function mysubmit() {
        var b_location = <?php echo '['.implode(',' , $find_route_fields['LOCATION']).']'; ?>;
        b_location = getElemFilter( b_location, 'ORDER_PROP_' );
        b_location.removeAttribute('disabled');
        submitForm('Y');
    }

bindReady( function () {

    var routewidget_window = getElem('routewidget_window');
    // Добавление окна в конец body
    document.body.appendChild(routewidget_window);

    // Закрытие окна с виджетом
    addEvent( document, 'click', function (e) {
        if (e.target.id == 'routewidget_window_close') {
            hideElem(getElem('routewidget_window'));
            document.body.style.overflow = 'auto';
        }
    } );

    // Открытие окна с виджетом, кнопка <Выбрать адрес доставки>
    addEvent(document, 'click', function (e) {

        if (e.target.id == 'select_address_delivery') {

            // Показ окна с виджетом
            showElem(getElem('routewidget_window'));

            // Инициализация виджета
            window.marschrouteWidget = window.marschrouteWidget || new Widget({
                public_key: '<?php echo $param_route['public_key']['VALUE']?>',
                target_id: 'routewidget',

                // Обработка <Подтвердить выбор доставки>
                onSubmit: function (delivery, widget) {

                    // Заполнение полей Маршрута
                    route_cost.value = delivery.delivery_cost;
                    route_kladr.value = delivery.city_id;
                    route_place.value = delivery.place_id;


                    var b_address = <?php echo '['.implode(',' , $find_route_fields['ADDRESS']).']'; ?>;
                    var b_location = <?php echo '['.implode(',' , $find_route_fields['LOCATION']).']'; ?>;

                    var b_address = getElemFilter(b_address, 'ORDER_PROP_');

                    var b_location = getElemFilter( b_location, 'ORDER_PROP_' );

                    // Заполнение полей адреса
                    b_address.value = delivery.address +
                        ((delivery.building_1) ? ' д.' + delivery.building_1 : '') +
                        ((delivery.room) ? ' кв.' + delivery.room : '');


                    var b_location_select = b_location.options[ b_location.options.selectedIndex ];

                    for(var n in  b_location.options) {
                        if (b_location.options[n].value) {

                            if (b_location.options[n].innerHTML == delivery.city_name) {
                                b_location_select.removeAttribute('selected');
                                b_location.options[n].setAttribute('selected', 'selected');
                                break;
                            }
                        }
                    }

                    hideElem(getElem('routewidget_window'));

                }
            });

            // Показ виджета
            marschrouteWidget.open({
                weight: <?php echo $arParams['ORDER']['ORDER_WEIGHT']; ?>,
                sum: <?php echo $arParams['ORDER']['ORDER_PRICE']; ?>,
                <?php // По умолчанию габариты 100x100x100 миллиметров ?>
                size: [100, 100, 100]
            });
        }
    });
});
</script>


<?php
$checked = (bool)$arParams['ORDER']['DELIVERY']['Route']['PROFILES']['simple']['CHECKED'];
if ($checked):
?>
    <input id="select_address_delivery" type='button' value='Выбрать адрес доставки' style="margin: 5px"/> <br/>

<script type="text/javascript">

bindReady( function () {

    var submitbutton = document.getElementsByName('submitbutton');

    for(var n = 0; n < submitbutton.length; n++) {
        submitbutton[n].setAttribute('onclick', 'mysubmit()');
    }



    var b_address = <?php echo '['.implode(',' , $find_route_fields['ADDRESS']).']'; ?>;
    var b_location = <?php echo '['.implode(',' , $find_route_fields['LOCATION']).']'; ?>;

    var b_address = getElemFilter(b_address, 'ORDER_PROP_');

    var b_location = getElemFilter( b_location, 'ORDER_PROP_' );

    var b_country = getElem("COUNTRY_" + b_location.id + b_location.id);


    b_country.setAttribute('disabled', 'disabled');
    b_location.setAttribute('disabled', 'disabled');
    b_address.setAttribute('readonly', 'readonly');
});

</script>

<?php endif; ?>