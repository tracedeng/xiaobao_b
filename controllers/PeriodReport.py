#!/usr/bin/python
# -*- coding: utf-8 -*-

import socket
import select
#import Queue
import geohash
import httplib
import json
import urllib

import logging
from logging.handlers import RotatingFileHandler

__author__ = 'tracedeng'

def init_logger():
	Rthandler = RotatingFileHandler('/var/ftp/pub/myapp.log', maxBytes=10*1024*1024,backupCount=5)
	#Rthandler = RotatingFileHandler('myapp.log', maxBytes=10*1024*1024,backupCount=5)
	formatter = logging.Formatter('%(asctime)s %(filename)s:%(lineno)s %(name)s - %(message)s')
	#formatter = logging.Formatter('%(name)-12s: %(levelname)-8s %(message)s')
	Rthandler.setFormatter(formatter)
	logging.getLogger('').addHandler(Rthandler)
	logging.getLogger('').setLevel(logging.DEBUG)

def gps_packet(l, cliAddr):
	try:
		if len(l) == 11:
			[imei, time, lng, lat, lbslng, lbslat, steps, state, chargestate, battery, voltage] = l
			#[imei, time, lng, lat, pwr, switch, spd, _, _, _, lbslng, lbslat, adc] = l
			#logging.debug("imei:%s, time:%s, lng:%s, lat:%s, pwr:%s, switch:%s, spd:%s, _:%s, _:%s, _:%s, lbslng:%s, lbslat:%s, adc:%s", l)
			logging.debug("imei:%s, time:%s, lng:%s, lat:%s, steps:%s, battery:%s, voltage:%s", imei, time, lng, lat, steps, battery, voltage)

			#ip, port = cliAddr
			addr = json.JSONEncoder().encode({"ip":cliAddr[0], "port":cliAddr[1]})
			if (len(lng) != 0) and (len(lat) != 0):
				lng = float(lng)
				lat = float(lat)
				lng = lng // 100 + (lng / 100 - lng // 100) * 100 / 60
				lat = lat // 100 + (lat / 100 - lat // 100) * 100 / 60
			position = json.JSONEncoder().encode({"lng":lng, "lat":lat})
			params = {'skey': '', 'opcode': '50', 'gprsId': imei, 'motionIndex': steps, 'battery': battery, 'position': position, 'cliaddr':addr}
		#else if len(l) > 11:


		#params = urllib.urlencode({'name': 'tom', 'age': 22})
		headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
		#logging.debug("params:%s", params)
		params = urllib.urlencode(params)
		conn = httplib.HTTPConnection("182.254.159.219", 80, timeout = 1)
		conn.request("POST", "/basic/web/?r=hardware/operate", params, headers)
		response = conn.getresponse()
		logging.debug("status=%d, reason=%s", response.status, response.reason)

	except ValueError, e:
		#上报数据错误
		logging.error("ValueError, %s", e)
	except Exception, e:
		logging.error("%s", e)

#IMEI,7,SEC_SWITCH_ON,SEC_SWITCH_OFF
def modify_gps_package_freq(l):
	#exec内部 需要import
	import socket
	try:
		[imei, order, destip, destport] = l
		command = ",".join([imei, order, "60"])
		logging.debug("modify gps package frequence, command:%s, destination:%s:%s", command, destip, destport)
		clientSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
		clientSocket.sendto(command, (destip, int(destport)))
		clientSocket.sendto(command, (destip, int(destport)))
		#data2, cliAddr2 = clientSocket.recvfrom(1024)
		#if data2:
		#	logging.debug("收到数据：%s, 客户端：%s", data2, cliAddr2)
		#clientSocket.close()
	except ValueError, e:
		#指令数据格式有误
		logging.error("ValueError, %s", e)
	except Exception, e:
		logging.error("%s", e)

#IMEI,16,serverdns,port
def change_server_ip(l):
	command = ','.join(l)

init_logger()
try:
	serverSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	serverSocket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
	server_address = ("0.0.0.0", 9527)
	#server_address = ("115.47.56.129", 9527)
	serverSocket.bind(server_address)
	# serverSocket.listen(1)

	serverSocket.setblocking(0)
	timeout = 10
	#新建epoll事件对象，后续要监控的事件添加到其中
	epoll = select.epoll()
	#添加服务器监听fd到等待读事件集合
	epoll.register(serverSocket.fileno(), select.EPOLLIN)
	#message_queues = {}
except Exception, e:
	logging.error('服务器启动失败')
logging.debug('服务器启动成功，监听IP：%s' , server_address)

fd_to_socket = {serverSocket.fileno():serverSocket,}
while True:
	#print "等待活动连接......"
	#轮询注册的事件集合
	events = epoll.poll(timeout)
	if not events:
		#logging.debug("epoll超时无活动连接，重新轮询......")
		continue
	#logging.debug("有%d个新事件，开始处理......", len(events))
	for fd, event in events:
		socket = fd_to_socket[fd]
		#可读事件
		if event & select.EPOLLIN:
			data, cliAddr = socket.recvfrom(1024)
			if data:
				logging.debug("收到数据：%s, 客户端：%s", data, cliAddr)
				l = data.split(',')
				if len(l[1]) == len('20150531160830'):
					#Report Package 上报数据
					d = {"888":"RockPacket", "501":"GasPacket", "666":"LowVotageWarning", "500":"AlarmReport", "999":"SOSPacket"}
					#bool(d.has_key(l[2])) and gps_packet(l) or (exec(d[l[2]] + '()'))
					if d.has_key(l[1]):
						exec(d[l[1]] + '(l)')
					else:
						gps_packet(l, cliAddr)
					#socket.sendto('Received %s bytes from %s' % len(data), cliAddr)
				else:
					#Command 命令
					#logging.error(l[1])
					d = {"1":"Deprecated", "2":"soft_reset", "7":"modify_gps_package_freq", "16":"change_server_ip", "17":"change_server_port"}
					if d.has_key(l[1]):
						exec(d[l[1]] + '(l)')
					else:
						#不识别的命令
						logging.debug("unkown command")
		elif select.EPOLLHUP & events:
			#epoll_wait被更高级的系统调用打断，忽略这种错误
			logging.error("epoll interrupt by system")

epoll.unregister(serverSocket.fileno())
epoll.close()
serverSocket.close()
