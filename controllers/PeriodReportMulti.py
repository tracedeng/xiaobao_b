#!/usr/bin/python
# -*- coding: utf-8 -*-

import socket
import select
#import Queue
import geohash
import httplib
import json
import urllib
import multiprocessing
import os

import logging
from logging.handlers import RotatingFileHandler

__author__ = 'tracedeng'

def init_logger():
	#Rthandler = RotatingFileHandler('myapp.log', maxBytes=10*1024*1024,backupCount=5)
	Rthandler = RotatingFileHandler('/var/ftp/pub/myapp.log', maxBytes=10*1024*1024,backupCount=5)
	formatter = logging.Formatter('%(asctime)s %(filename)s:%(lineno)s %(name)s[%(process)d] - %(message)s')
	#formatter = logging.Formatter('%(name)-12s: %(levelname)-8s %(message)s')
	Rthandler.setFormatter(formatter)
	logging.getLogger('').addHandler(Rthandler)
	logging.getLogger('').setLevel(logging.DEBUG)

def event_loop(name):
	global epoll
	#global fd_to_socket
	global serverSocket
	#print 'Run task %s (%s)...' % (name, os.getpid())

	#epoll = select.epoll()
	#添加服务器监听fd到等待读事件集合
	#epoll.register(serverSocket.fileno(), select.EPOLLIN)

	while True:
		#print "等待活动连接......"
		#轮询注册的事件集合
		try:
			events = epoll.poll(timeout)
			if not events:
				#logging.debug("epoll超时无活动连接，重新轮询......")
				continue
			#logging.debug("有%d个新事件，开始处理......", len(events))
			for fd, event in events:
				#socket = fd_to_socket[fd]
				socket = serverSocket
				#可读事件
				if event & select.EPOLLIN:
					data, cliaddr = socket.recvfrom(1024)
					if data:
						logging.debug("收到数据：%s, 客户端：%s", data, cliaddr)
						cmd = data[0:1]
						(data_report if cmd in ['$', '@', '#', '!'] else deal_command)(data, cliaddr)
						#d = {'$':period_report, '@':realtime_report, '#':nogsm_report}
						#else:
						#	#Command 命令
						#	#dc = {"1":"Deprecated", "2":"soft_reset", "7":"modify_gps_package_freq", "16":"change_server_ip", "17":"change_server_port"}
						#	#dc = {"1":Deprecated, "2":soft_reset, "7":modify_gps_package_freq, "16":change_server_ip, "17":change_server_port}
						#	dc = {"7":modify_gps_package_freq, "16":change_server_ip}
						#	if dc.has_key(l[2]):
						#		#exec(d[l[2]] + '()')
						#		dc[l[2]]()
						#	else:
						#		#不识别的命令
						#		logging.debug("unkown command")
				elif select.EPOLLHUP & events:
					#epoll_wait被更高级的系统调用打断，忽略这种错误
					logging.error("epoll interrupt by system")
		except IOError, e:
			#惊群
			pass
		except Exception, e:
			logging.debug("%s", e)
	
def data_report(data, cliaddr):
	l = data[1:].split(',')

	#设备刚上电，无法获取时间
	if len(l[1]) == len('20150531160830') or len(l[1]) == len('20150531') or '!' == data[0]:
		gps_packet(l, cliaddr, data[0:1])
	else:
		#不识别的命令
		logging.debug("unkown command")

def gps_packet(l, cliaddr, type):
	try:
		import time
		serverTime = time.strftime("%Y%m%d%H%M%S", time.localtime())
		ip, port = cliaddr
		addr = json.JSONEncoder().encode({"ip":cliaddr[0], "port":cliaddr[1]})

		if type is '$':
			#20分钟周期上报数据转换成序号
			[imei, time, lac, cellid, signal, imsi, steps, chargestate, battery, voltage] = l
			logging.debug("imei:%s, time:%s, lac:%s, cellid:%s, signal:%s, steps:%s, chargestate:%s, battery:%s, voltage:%s, imsi:%s", 
				imei, time, lac, cellid, signal, steps, chargestate, battery, voltage, imsi)

			#if len(l[1]) == len('20150531160830'):
			#	seq = int(time[8:10]) * 3 + int(time[10:12]) / 20 + 1
			#else:
			#	seq = int(serverTime[8:10]) * 3 + int(serverTime[10:12]) / 20 + 1
			seq = int(serverTime[8:10]) * 3 + int(serverTime[10:12]) / 20 + 1
			#motionIndex = json.JSONEncoder().encode([steps])

			signal = (int)(signal) * 2 - 113
			position = json.JSONEncoder().encode({"lac":lac, "cellid":cellid, 'signal':signal})
			params = {'skey': '', 'opcode': '50', 'type': '$', 'gprsId': imei, 'deviceTime': time, 'seq' : seq, 'motionIndex': motionIndex, 'battery': battery, 'position': position, 'trans' : 1, 'cliaddr':addr}
		elif type is '@':
			#实时定位
			[imei, time, lng, lat, lac, cellid, signal, steps, chargestate, battery, voltage, imsi] = l
			logging.debug("imei:%s, time:%s, lng:%s, lat:%s, lac:%s, cellid:%s, signal:%s, steps:%s, chargestate:%s, battery:%s, voltage:%s, imsi:%s", 
				imei, time, lng, lat, lac, cellid, signal, steps, chargestate, battery, voltage, imsi)

			if lng is '' or lat is '' or lng is '0' or lat is '0':
				#没有GPS信号
				position = json.JSONEncoder().encode({"lac":lac, "cellid":cellid, 'signal':signal})
				trans = 1;
			else:
				#火星坐标转换
				lng = float(lng)
				lat = float(lat)
				lng = lng // 100 + (lng / 100 - lng // 100) * 100 / 60
				lat = lat // 100 + (lat / 100 - lat // 100) * 100 / 60
				position = json.JSONEncoder().encode({"lng":lng, "lat":lat})
				trans = 2;

			motionIndex = json.JSONEncoder().encode([steps])
			params = {'skey': '', 'opcode': '50', 'type': type, 'gprsId': imei, 'deviceTime': time, 'seq' : 0, 'motionIndex': motionIndex, 'battery': battery, 'position': position, 'trans' : trans, 'cliaddr':addr}
		elif type is '!':
			#每5分钟回时间给设备
			imei = l[0]
			#logging.debug("imei:%s", imei)
			motionIndex = json.JSONEncoder().encode([])
			position = json.JSONEncoder().encode({"lng":0, "lat":0})
			params = {'skey': '', 'opcode': '50', 'type': type, 'gprsId': imei, 'deviceTime': serverTime, 'cliaddr':addr}
			#params = {'skey': '', 'opcode': '50', 'type': type, 'gprsId': imei, 'deviceTime': serverTime, 'seq' : 0, 'motionIndex': motionIndex, 'battery': 0, 'position': position, 'trans' : 0, 'cliaddr':addr}
			#下发服务器时间到设备
			logging.debug("imei:%s", imei)
			global serverSocket
			command = ",".join([imei, '21', serverTime])
			logging.debug("every 5 minite send server time to device, command:%s, destination:%s", command, cliaddr)
			serverSocket.sendto(command, cliaddr)
		elif type == '#':
			#丢失gsm信号，多包
			imei = l[0]
			motionIndex = []
			for motion in l[1:]:
				motionIndex.append(motion)

			seq = json.JSONEncoder().encode(seq)
			motionIndex = json.JSONEncoder().encode(motionIndex)
			position = json.JSONEncoder().encode({"lng":0, "lat":0})

			params = {'skey': '', 'opcode': '50', 'type': '#', 'gprsId': imei, 'deviceTime': serverTime, 'seq' : 0, 'motionIndex': motionIndex, 'battery': 0, 'position': position, 'trans' : 0, 'cliaddr':addr}

		#params = urllib.urlencode({'name': 'tom', 'age': 22})
		logging.debug("params:%s", params)
		params = urllib.urlencode(params)
		headers = {"Content-type": "application/x-www-form-urlencoded", "Accept": "text/plain"}
		conn = httplib.HTTPConnection("139.196.41.147", 80, timeout = 1)
		#conn = httplib.HTTPConnection("182.254.159.219", 80, timeout = 1)
		conn.request("POST", "/basic/web/?r=hardware/operate", params, headers)
		#response = conn.getresponse()
		#logging.debug("status=%d, reason=%s", response.status, response.reason)

	except ValueError, e:
		#上报数据错误
		logging.error("ValueError, %s", e);
	#except Exception, e:
		#logging.error("%s", e);

def deal_command(data, cliaddr):
	dc = {"7":modify_gps_package_freq, "19":change_server_ip, "21":fetch_multi_pack}

	l = data.split(',')
	dc.get(l[1])(l)

def fetch_multi_pack(l):
	#exec内部 需要import
	global serverSocket
	import socket
	try:
		[imei, order, destip, destport] = l
		command = ",".join([imei, order, "1"])
		logging.debug("fetch multiple package, command:%s, destination:%s:%s", command, destip, destport)
		#clientSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
		#clientSocket.sendto(command, (destip, int(destport)))
		#clientSocket.sendto(command, (destip, int(destport)))
		serverSocket.sendto(command, (destip, int(destport)))
		#data2, cliAddr2 = clientSocket.recvfrom(1024)
		#if data2:
		#	logging.debug("收到数据：%s, 客户端：%s", data2, cliAddr2)
		#clientSocket.close()
	except ValueError, e:
		#指令数据格式有误
		logging.error("ValueError, %s", e)
	except Exception, e:
		logging.error("%s", e)
	#pass

#IMEI,7,SEC_SWITCH_ON,SEC_SWITCH_OFF
def modify_gps_package_freq(l):
	#exec内部 需要import
	global serverSocket
	import socket
	try:
		[imei, order, destip, destport] = l
		command = ",".join([imei, order, "1"])
		logging.debug("modify gps package frequence, command:%s, destination:%s:%s", command, destip, destport)
		#clientSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
		#clientSocket.sendto(command, (destip, int(destport)))
		#clientSocket.sendto(command, (destip, int(destport)))
		serverSocket.sendto(command, (destip, int(destport)))
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
	global serverSocket
	import socket
	try:
		[imei, order, destip, destport, newip, newport] = l
		command = ",".join([imei, order, newip, newport])
		logging.debug("modify server destination, command:%s, destination:%s:%s", command, destip, destport)
		#clientSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
		#clientSocket.sendto(command, (destip, int(destport)))
		#clientSocket.sendto(command, (destip, int(destport)))
		serverSocket.sendto(command, (destip, int(destport)))
		#data2, cliAddr2 = clientSocket.recvfrom(1024)
		#if data2:
		#	logging.debug("收到数据：%s, 客户端：%s", data2, cliAddr2)
		#clientSocket.close()
	except ValueError, e:
		#指令数据格式有误
		logging.error("ValueError, %s", e)
	except Exception, e:
		logging.error("%s", e)

	#command = ','.join(l)
	#logging.debug("modify gps package frequence, command:%s, destination:%s", command, dest)
	#s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	#s.sendto(command, dest)

init_logger()
try:
	serverSocket = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
	serverSocket.setsockopt(socket.SOL_SOCKET, socket.SO_REUSEADDR, 1)
	server_address = ("0.0.0.0", 9527)
	#server_address = ("115.47.56.129", 9527)
	serverSocket.bind(server_address)
	# serverSocket.listen(1)
	#fd_to_socket = {serverSocket.fileno():serverSocket,}

	serverSocket.setblocking(0)
	timeout = 10
	#新建epoll事件对象，后续要监控的事件添加到其中
	epoll = select.epoll()
	#添加服务器监听fd到等待读事件集合
	epoll.register(serverSocket.fileno(), select.EPOLLIN)
	#message_queues = {}
except Exception, e:
	logging.error('服务器启动失败')
logging.debug('服务器启动成功，监听IP：%s', server_address)

#pool = multiprocessing.Pool(3)
#for i in xrange(3):
#	pool.apply_async(event_loop, (i + 1, ))
event_loop(0)
pool.close()
pool.join()
logging.debug('所有进程处理完毕')
epoll.unregister(serverSocket.fileno())
epoll.close()
serverSocket.close()
