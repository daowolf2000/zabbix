#!/bin/bash
# Скрипт бэкапа с помощью LVM
# Предполагается что /opt/zbx расположен на отдельном LV

zbx_db=zbx_db_1                      # Имя контейнера с БД  
zbx_user=zabbix                      # Имя пользователя БД
snapshot_name=zbx_backup_snap        # Имя снапшота для бэкапа
snapshot_size=5G                     # Размер снапшота
vg=vg_zbx                            # Имя VG для бэкапа
lv=zbx                               # Имя LV для бэкапа
backup_folder=/mnt/backup            # Имя каталога для сохранения бэкапа
backup_name=zbx                      # Префикс имени бэкапа ${backup_name}_${day}.tgz
keep_num=3                           # Количество хранимых копий
force=false                          # Использовать форсированный режим для перевода БД в режим бэкапа
nice=10                              # Понижение приоритета выполнения архивирования (от 0 до 20) - чем выше число, тем ниже приоритет

## Удаляем старые бэкапы###
echo "Запускаем очистку устаревших бэкапов"
OIFS="$IFS"; IFS=$'\n'; x=1
echo "Найдены следующие бэкапы:"
ls -lt1 ${backup_folder}/${backup_name}*.tgz
for i in `ls -t1 ${backup_folder}/${backup_name}*.tgz`; do
		if [ $x -le ${keep_num} ]; then ((x++)); continue; fi
		echo "Удаляем старый архив - $i"
		rm "$i"
done
IFS="$OIFS"


echo "Старт бэкапа"
day=`date +"%y.%m.%d-%H.%M.%S"`

#Проверяем отсутвие снапшота, удаляем, если находим
if (( "`ls /dev/${vg}/${snapshot_name} 2>/dev/null |grep ${snapshot_name}|wc -l`" > 0 )); then 
	echo "Обнаружен старый снапшот /dev/${vg}/${snapshot}, удаляем"
	lvremove --autobackup y -f /dev/${vg}/${snapshot_name}
fi

    
# Если запущена БД, то переводим останавливаем запись 
if (( "`docker ps | grep timescale | grep Up |wc -l`" > 0)); then
	echo "Запущена БД Zabbix, посылаем pg_start_backup('backup zbx')"
	docker exec -u postgres ${zbx_db} psql -U ${zbx_user} -c "SELECT pg_start_backup('backup zbx');"
fi

# Создаем снапшот
echo "Создаем снапшот для бэкапа /dev/${vg}/${snapshot_name}"
lvcreate -y --autobackup y --size ${snapshot_size} --snapshot --name ${snapshot_name} -p r /dev/${vg}/${lv}

# Если запущена БД, то восстанавливаем запись
if (( "`docker ps | grep timescale | grep Up |wc -l`" > 0)); then
	echo "Запущена БД Zabbix, посылаем pg_stop_backup()"
	docker exec -u postgres ${zbx_db} psql -U ${zbx_user} -c "SELECT pg_stop_backup();"
fi


# Монтируем снапшот
echo "Монтируем снапшот /dev/${vg}/${snapshot_name} в каталог /mnt/${vg}_${snapshot_name}"
mkdir /mnt/${vg}_${snapshot_name} 2>/dev/null
mount /dev/${vg}/${snapshot_name} /mnt/${vg}_${snapshot_name}


rm ${backup_folder}/${backup_name}_${day}.tgz 2>/dev/null
echo "Архивируем содержимое снапшота в ${backup_folder}/${backup_name}_${day}.tgz"
nice -n ${nice} tar czf ${backup_folder}/${backup_name}_${day}.tgz /mnt/${vg}_${snapshot_name} && echo "Архивирование успешно завершено" || echo "Архивирование завершилось с ошибкой"


# Демонтируем и удаляем снапшот
echo "Демонтируем и удаляем снапшот /dev/${vg}/${snapshot_name}"
umount /mnt/${vg}_${snapshot_name}
lvremove --autobackup y -f /dev/${vg}/${snapshot_name}

echo "Конец бэкапа"
