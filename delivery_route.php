<?php
/*
"SID" => "Route",
"NAME" => "Маршрут",
"DESCRIPTION" => "",
"DESCRIPTION_INNER" =>
*/


//CModule::IncludeModule("sale");

class CDeliveryMySimple
{
    function Init()
    {
        return array(
            /* Основное описание */
            "SID" => "Route",
            "NAME" => "Маршрут",
            "DESCRIPTION" => "",
            "DESCRIPTION_INNER" =>
                "Простой обработчик МАРШРУТНОЙ доставки. Для функционирования необходимо "
                ."наличие хотя бы одной группы местоположений. При настройке обработчика указывается "
                ."фиксированная стоимость доставки для каждой группы местоположений. Для того, чтобы "
                ."группа не участвовала в обработке, оставьте пустым поле стоимости для этой группы."
                ."<br />"
                ."<a href=\"/bitrix/admin/sale_location_group_admin.php?lang=ru\" target=\"_blank\">"
                ."Редактировать группы местоположений"
                ."</a>.",
            "BASE_CURRENCY" => COption::GetOptionString("sale", "default_currency", "RUB"),

            "HANDLER" => __FILE__,

            /* Методы обработчика */
            "DBGETSETTINGS" => array("CDeliveryMySimple", "GetSettings"),
            "DBSETSETTINGS" => array("CDeliveryMySimple", "SetSettings"),
            "GETCONFIG" => array("CDeliveryMySimple", "GetConfig"),

            "COMPABILITY" => array("CDeliveryMySimple", "Compability"),
            "CALCULATOR" => array("CDeliveryMySimple", "Calculate"),

            /* Список профилей доставки */
            "PROFILES" => array(
                "simple" => array(
                    "TITLE" => "доставка",
                    "DESCRIPTION" => "Срок доставки до 3 дней",

                    "RESTRICTIONS_WEIGHT" => array(0), // без ограничений
                    "RESTRICTIONS_SUM" => array(0), // без ограничений
                ),
            )
        );
    }

    // настройки обработчика
    function GetConfig()
    {
        $arConfig = array(
            "CONFIG_GROUPS" => array(
                "route" => "Параметры Виджета"
            ),

            "CONFIG" => array(
                "public_key" => array(
                    "TITLE" => "Публичный ключ",
                    "TYPE" => "STRING",
                    "GROUP" => "route"
                )
            ),
        );

        // настройками обработчика в данном случае являются значения стоимости доставки в различные группы местоположений.
        // для этого сформируем список настроек на основе списка групп

        $dbLocationGroups = CSaleLocationGroup::GetList();
        while ($arLocationGroup = $dbLocationGroups->Fetch())
        {
            $arConfig["CONFIG"]["price_".$arLocationGroup["ID"]] = array(
                "TYPE" => "STRING",
                "DEFAULT" => "",
                "TITLE" =>
                    "Стоимость доставки в группу \""
                    .$arLocationGroup["NAME"]."\" "
                    ."(".COption::GetOptionString("sale", "default_currency", "RUB").')',
                "GROUP" => "all",
            );
        }

        return $arConfig;
    }

    // подготовка настроек для занесения в базу данных
    function SetSettings($arSettings)
    {
        // Проверим список значений стоимости. Пустые значения удалим из списка.
/*
        foreach ($arSettings as $key => $value)
        {
            if (strlen($value) > 0)
                $arSettings[$key] = doubleval($value);
            else
                unset($arSettings[$key]);
        }
*/
        // вернем значения в виде сериализованного массива.
        // в случае более простого списка настроек можно применить более простые методы сериализации.
        return serialize($arSettings);
    }

    // подготовка настроек, полученных из базы данных
    function GetSettings($strSettings)
    {
        // вернем десериализованный массив настроек
        return unserialize($strSettings);
    }

    // введем служебный метод, определяющий группу местоположения и возвращающий стоимость для этой группы.
    function __GetLocationPrice($LOCATION_ID, $arConfig)
    {
        // получим список групп для переданного местоположения
        $dbLocationGroups = CSaleLocationGroup::GetLocationList(array("LOCATION_ID" => $LOCATION_ID));

        while ($arLocationGroup = $dbLocationGroups->Fetch())
        {
            if (
                array_key_exists('price_'.$arLocationGroup["LOCATION_GROUP_ID"], $arConfig)
                &&
                strlen($arConfig['price_'.$arLocationGroup["LOCATION_GROUP_ID"]]["VALUE"] > 0)
            )
            {
                // если есть непустая запись в массиве настроек для данной группы, вернем ее значение
                return $arConfig['price_'.$arLocationGroup["LOCATION_GROUP_ID"]]["VALUE"];
            }
        }

        // если не найдено подходящих записей, вернем false
        return false;
    }

    // метод проверки совместимости в данном случае практически аналогичен рассчету стоимости
    function Compability($arOrder, $arConfig)
    {
        //
        return array('simple');
        // проверим наличие стоимости доставки
        $price = CDeliveryMySimple::__GetLocationPrice($arOrder["LOCATION_TO"], $arConfig);

        if ($price === false)
            return array(); // если стоимость не найдено, вернем пустой массив - не подходит ни один профиль
        else
            return array('simple'); // в противном случае вернем массив, содержащий идентфиикатор единственного профиля доставки
    }

    // собственно, рассчет стоимости
    function Calculate($profile, $arConfig, $arOrder, $STEP, $TEMP = false)
    {
        // служебный метод рассчета определён выше, нам достаточно переадресовать на выход возвращаемое им значение.
        return array(
            "RESULT" => "OK",
            "VALUE" => CDeliveryMySimple::__GetLocationPrice($arOrder["LOCATION_TO"], $arConfig)
        );
    }
}

// установим метод CDeliveryMySimple::Init в качестве обработчика события
AddEventHandler("sale", "onSaleDeliveryHandlersBuildList", array('CDeliveryMySimple', 'Init'));