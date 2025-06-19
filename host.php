<?php
include 'db.php';
$hostid = $_GET['hostid'];

// Соединение с БД
$dbconn = pg_connect($conn_str)
    or die('Не удалось соединиться с БД: ' . pg_last_error());
    
?>

<style>
.dw_host_info {	border-spacing: 0px; }
.dw_host_info td {	border: #E0E0E0 1px solid; padding: 4px 16px;}
.dw_host_info td.dw_name {	font-weight:bold; padding: 4px 16px; vertical-align: top; }
.dw_host_conn {	border-spacing: 0px; }
.dw_host_conn th {	border: #E0E0E0 1px solid; padding: 4px 16px; border-spacing: 0px; }
.dw_host_conn td {	border: #E0E0E0 1px solid; padding: 4px 16px;}
.dw_problem_list td {  padding: 4px 8px; vertical-align: top; }
.dw_problem_table {	border-spacing: 0px; }
.dw_problem_table th {	font-size: 14px; color: #666666; border: #E0E0E0 1px solid; padding: 4px 16px; }
.dw_problem_table td {	font-size: 13px; border-spacing: 0px; padding: 4px 16px; vertical-align: top;border: #E0E0E0 1px solid; }
td.dw_hide { vertical-align: top; max-width:50%; }
td.dw_hide2 { padding-left: 24px; vertical-align: top; max-width:50%; }
td.dw_hide2 table {	border-spacing: 0px; }
td.dw_hide2 td { border: #E0E0E0 1px solid; padding: 4px 16px;}


td.dw_hide2 td:nth-child(2)  { line-break: anywhere; }

.dw_triggers {	border-spacing: 0px; }
.dw_triggers th {	border: #E0E0E0 1px solid; padding: 4px 16px; vertical-align: top;}
.dw_triggers td {	border: #E0E0E0 1px solid; padding: 4px 16px; vertical-align: top;}
.dw_triggers td.value {	text-align: center; }
.dw_triggers td.name {	padding: 4px 16px; vertical-align: top; }
.dw_triggers td.priority1 {	background-color: #97AAB3; }
.dw_triggers td.priority2 {	background-color: #4FC3F7; }
.dw_triggers td.priority3 {	background-color: #FFFF00; }
.dw_triggers td.priority4 {	background-color: #E97659; }
.dw_triggers td.priority5 {	background-color: #E45959; }
.dw_triggers td.priority0 {	background-color: #97AAB3; }


</style>



<body style="background-color: white; padding-left: 12px; padding-top:12px;">
<?php
$query = '
SELECT
    h.host,
    h.name,
    h.description,
    hi.name "dns-name",
    hi.type_full,
    hi.type,
    hi.os,
    hi.contact,
    hi.location,
    hi.hardware,
    hi.site_address_a,
    hi.site_address_b,
    hi.site_address_c
FROM 
    public.hosts h
    LEFT JOIN public.host_inventory hi ON h.hostid=hi.hostid
WHERE
    h.hostid='.$hostid;

$result = pg_query($query) or die('Ошибка запроса: ' . pg_last_error());
$host = pg_fetch_array($result, null, PGSQL_ASSOC);


?>
<table class="dw_hide">
<tr><td class="dw_hide">
<table class="dw_host_info">
<tr><td class="dw_name">Имя объекта </td><td><?php echo $host['name']; ?> </td></tr>
<tr><td class="dw_name">Группа доступа</td><td><?php echo $host['site_address_c']; ?> </td></tr>
<tr><td class="dw_name">Система</td><td><?php echo $host['type_full']; ?> </td></tr>
<tr><td class="dw_name">Тип объекта</td><td><?php echo $host['type']; ?> </td></tr>
<tr><td class="dw_name">Модель </td><td><?php echo $host['hardware']; ?> </td></tr>
<tr><td class="dw_name">Отделение </td><td><?php echo $host['os']; ?> </td></tr>
<tr><td class="dw_name">Местоположение </td><td><?php echo $host['site_address_a']; ?> / <?php echo $host['site_address_b']; ?><?php if ($host['location'] != '') { ?> (<?php echo $host['location']; ?>) <?php } ?></td></tr>
<?php if ($host['dns-name'] != '') { ?><tr><td class="dw_name">Сетевое имя </td><td><?php echo $host['dns-name']; ?> </td></tr> <?php } ?>
<tr><td class="dw_name">Подключение </td><td>
<?php
$query = '
SELECT 
    case 
        when i.type = 1 then \'Agent\'  
        when i.type = 2 then \'SNMP\'  
        when i.type = 3 then \'IPMI\'  
        when i.type = 4 then \'JMX\'  
        else (i.type)||\'\'
    end "type",
    i.ip "ip",
    i.port "port",
    case 
        when i.available = 0 then \'Неизвестно\'  
        when i.available = 1 then \'Доступен\'  
        when i.available = 2 then \'Недоступен\'  
        else (i.available)||\'\'
    end "state"
FROM 
    public.interface i
WHERE 
    hostid = '.$hostid;

$result = pg_query($query) or die('Ошибка запроса: ' . pg_last_error());
$conn_num = pg_num_rows($result);
$conn = pg_fetch_array($result, null, PGSQL_ASSOC);
//while ($conn = pg_fetch_array($result, null, PGSQL_ASSOC)) {
for ($i = 0; $i < $conn_num; $i++) {
	echo $conn['type']." - ".$conn['ip'].":".$conn['port']." (".$conn['state'].")";
	if ($i != $conn_num ) { echo "<br>"; } ;
}
?>
 </td></tr>
<?php if ($host['contact'] != '') { ?><tr><td class="dw_name">Ответственные</td><td style="white-space:nowrap"><?php echo str_replace(array("\r\n", "\r", "\n"), '<br>', $host['contact']); ?> </td></tr><?php } ?>
<?php if ($host['description'] != '') { ?><tr><td class="dw_name">Описание </td><td><?php echo str_replace(array("\r\n", "\r", "\n"), '<br>', $host['description']); ?> </td></tr><?php } ?>
</table>
</td><td class="dw_hide2">
<div id="dw_macros">
<h4> Переменные: </h4>
<?php
$query = '
select distinct on (dummy.macro) * from
(Select 
    hm.macro,
    hm.value,
    hm.description,
    2 as "type"
From
    hosts h
    Join hosts_templates ht on h.hostid = ht.hostid
    Join hostmacro hm on ht.templateid = hm.hostid 
Where
    h.hostid IN (Select templateid From hosts_templates Where hostid = \''.$hostid.'\')
    and hm.type=0
UNION
Select 
    hm.macro,
    hm.value,
    hm.description,
    1 as "type"
From
    hosts h
    Join hosts_templates ht on h.hostid = ht.hostid
    Join hostmacro hm on ht.templateid = hm.hostid 
Where
    h.hostid = \''.$hostid.'\'
    and hm.type=0
UNION
Select 
    hm.macro,
    hm.value,
    hm.description,
    0 as "type"
From
    hostmacro hm  
Where
    hm.hostid = \''.$hostid.'\'
order by 1,4 ) as dummy
where  dummy.macro not like \'{$SNMP.TRAP.TRASH.MATCH}\' and dummy.value not like \'CHANGE_IF_NEEDED\' and dummy.macro not like \'{$SNMP%\' and dummy.macro not like \'{$PWD%\' and dummy.macro not like \'{$DISCOVERY%\' 
';

$result = pg_query($query) or die('Ошибка запроса: ' . pg_last_error());

// Если результат запроса не пустой, то выводим таблицу
if (pg_num_rows($result)) {

	// Вывод результата в HTML
	echo "<table class=\"dw_macros_list\">";
	while ($line = pg_fetch_array($result, null, PGSQL_NUM)) {
		
		echo "<tr><td class=\"dw_name\">$line[0]</td><td>$line[1]</td><td>$line[2]</td></tr>";
	};
	echo "</table>";
}




?>


</div>
</td></tr></table>

<?php
$query = '
select 
	*
from (
Select distinct on (t.triggerid)
	t.value,
	t.priority,
	t.description,
	t.comments,
	t.status
from 
    items i
    join functions f on i.itemid = f.itemid
    join triggers t on  f.triggerid = t.triggerid
where 
    i.hostid = '.$hostid.'
	and i.flags in (0,4)
	and t.priority > 0
	) dw
    
    ';

$result = pg_query($query) or die('Ошибка запроса: ' . pg_last_error());

// Если результат запроса не пустой, то выводим таблицу
if (pg_num_rows($result)) {

	// Вывод результата в HTML
	echo "<h4>Отслеживаемые события:</h4>";
	echo "<table class=\"dw_triggers\">";
	echo "<tr><th>Состояние</th><th>Важность</th><th>Событие</th><th>Описание</th><th>Включено?</th></tr>";
	while ($line = pg_fetch_array($result, null, PGSQL_NUM)) {
		echo "<tr>";
		 if ($line[0]==0){ echo "<td class=\"value\" style=\"background-color: lightgreen;\">ОК</td>"; } else { echo "<td class=\"value\" style=\"background-color: lightcoral;\">ПРОБЛЕМА</td>";}
		 switch ($line[1]){
		    case 1: echo "<td class=\"priority1\">Информация</td>";  break;
		    case 2: echo "<td class=\"priority2\">Предупреждение</td>";  break;
		    case 3: echo "<td class=\"priority3\">Средняя</td>";  break;
		    case 4: echo "<td class=\"priority4\">Высокая</td>";  break;
		    case 5: echo "<td class=\"priority5\">Чрезвычайная</td>";  break;
		    default: echo "<td class=\"priority0\">Не определено</td>";  break;
		 }
		 echo"<td class=\"name\">$line[2]</td><td class=\"descr\">$line[3]</td>";
		 if ($line[4]==0){ echo "<td class=\"state\" style=\"color: green;\">Активно</td>"; } else { echo "<td class=\"state\" style=\"color: red;\">Отключено</td>";}
		 echo "</tr>";
	};
	echo "</table>";
}

?>


<?php

// Очистка результата
pg_free_result($result);

// Закрытие соединения
pg_close($dbconn);

?>
</body>
