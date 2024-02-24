<?php

require("classes/repositorio.php");


$vagas['A'] = '618	585	573	534	546	558	598	610	655	643	217	179	140	128	116	79	42	30	630/631
619	586	574	533	545	557	597	609	656	644	218	180	168	129	117	80	43	31	632/633
620	587	575	563	544	556	596	608	657	645	219	181	169	130	118	81	44	32	634/635
621	588	576	564	543	555	595	607	658	646	220	182	170	131	119	82	45	33	12/13
622	589	577	565	542	554	594	606	659	647	221	209	171	132	120	83	46	34	14/15
623	590	578	566	541	553	593	605	660	648	636	210	172	133	121	84	47	35	16/17
624	612	579	567	540	552	592	604	661	649	637	211	173	134	122	85	73	36	18/19
625	613	580	568	539	551	591	603	662	650	638	212	174	135	123	86	74	37	20/21
626	614	581	569	538	550	562	602	663	651	639	213	175	136	124	87	75	38	22/23
627	615	582	570	537	549	561	601	664	652	640	214	176	137	125	88	76	39	24/25
628	616	583	571	536	548	560	600	665	653	641	215	177	138	126	89	77	40	26/27
629	617	584	572	535	547	559	599	611	654	642	216	178	139	127	115	78	41	28/29';

$vagas['B'] = '521	509	497	485	473	430	418	5	227	265	277	308	320	332	362	393	405	444	451/452
522	510	498	486	474	431	419	4	226	264	276	307	319	331	361	373	404	445	453/454
523	511	499	487	475	432	420	3	225	263	275	306	318	330	360	372	403	446	455/456
524	512	500	488	476	433	421	2	224	262	274	305	317	329	359	371	402	447	457/458
525	513	501	489	477	434	422	1	223	261	273	304	316	328	358	370	401	448	459/460
526	514	502	490	478	435	423	411	222	234	272	303	315	327	357	369	400	449	461/462
527	515	503	491	479	467	424	412	11	233	271	302	314	326	356	368	399	450	463/464
528	516	504	492	480	468	425	413	10	232	270	301	313	325	355	367	398	410	465/466
529	517	505	493	481	469	426	414	9	231	269	300	312	324	354	366	397	409	436/437
530	518	506	494	482	470	427	415	8	230	268	299	311	323	353	365	396	408	438/439
531	519	507	495	483	471	428	416	7	229	267	298	310	322	352	364	395	407	440/441
532	520	508	496	484	472	429	417	6	228	266	278	309	321	333	363	394	406	442/443';

$vagas['C'] = '1395	1407	1369	1381	1357	1327	1339	1019	1031	1043	1006	980	992	958	970	388	376	294/295
1394	1406	1364	1380	1356	1326	1338	1018	1030	1042	1005	979	991	957	969	389	377	296/297
1393	1405	1365	1379	1355	1325	1337	1017	1029	1041	1004	978	990	956	968	390	378	334/335
1392	1404	1366	1378	1354	1324	1336	1016	1028	1040	1003	1015	989	955	967	391	379	336/337
1391	1403	1367	1377	1353	1323	1335	1347	1027	1039	1002	1014	988	954	966	392	380	338/339
1390	1402	1368	1376	1352	1322	1334	1346	1026	1038	1001	1013	987	953	965	977	381	340/341
1389	1401	1413	1375	1351	1363	1333	1345	1025	1037	1000	1012	986	952	964	976	382	342/343
1388	1400	1412	1374	1350	1362	1332	1344	1024	1036	999	1011	985	951	963	975	383	344/345
1387	1399	1411	1373	1349	1361	1331	1343	1023	1035	998	1010	984	996	962	974	384	346/347
1386	1398	1410	1372	1348	1360	1330	1342	1022	1034	997	1009	983	995	961	973	385	348/349
1385	1397	1409	1371	1383	1359	1329	1341	1021	1033	1045	1008	982	994	960	972	386	350/351
1384	1396	1408	1370	1382	1358	1328	1340	1020	1032	1044	1007	981	993	959	971	387	374/375';

$vagas['D'] = '1244	1232	1317	1305	1293	1281	1264	925	937	950	913	887	899	864	876	292	251	248/249
1245	1233	1318	1306	1294	1282	1265	924	936	948	912	886	898	863	875	291	250	246/247
1246	1234	1319	1307	1295	1283	1266	923	935	947	911	885	897	862	874	290	287	244/245
1247	1235	1320	1308	1296	1284	1267	922	934	946	910	884	896	861	873	289	286	242/243
1248	1236	1321	1309	1297	1285	1268	1256	933	945	909	921	895	860	872	288	285	240/241
1249	1237	1276	1310	1298	1286	1269	1257	932	944	908	920	894	859	871	883	284	238/239
1250	1238	1275	1311	1299	1287	1270	1258	931	943	907	919	893	858	870	882	283	236/237
1251	1239	1274	1312	1300	1288	1271	1259	930	942	906	918	892	857	869	881	282	235/260
1252	1240	1273	1313	1301	1289	1277	1260	929	941	905	917	891	856	868	880	281	258/259
1253	1241	1272	1314	1302	1290	1278	1261	928	940	904	916	890	902	867	879	280	256/257
1254	1242	1230	1315	1303	1291	1279	1262	927	939	903	915	889	901	866	878	279	254/255
1255	1243	1231	1316	1304	1292	1280	1263	926	938	949	914	888	900	865	877	293	252/253';

$vagas['E'] = '1153	1141	1223	1211	1199	1187	1173	831	843	855	820	794	806	770	782	198	157/158	154/155
1154	1142	1224	1212	1200	1188	1174	830	842	854	819	793	805	769	781	197	156/193	152/153
1155	1143	1225	1213	1201	1189	1175	829	841	853	818	792	804	768	780	196	191/192	150/151
1156	1144	1226	1214	1202	1190	1176	828	840	852	817	791	803	767	779	195	189/190	148/149
1157	1145	1227	1215	1203	1191	1177	1165	839	851	816	790	802	766	778	194	187/188	146/147
1158	1146	1228	1216	1204	1192	1178	1166	838	850	815	827	801	765	777	789	185/186	144/145
1159	1147	1229	1217	1205	1193	1179	1167	837	849	814	826	800	764	776	788	183/184	142/143
1160	1148	1182	1218	1206	1194	1180	1168	836	848	813	825	799	763	775	787	207/208	141/167
1161	1149	1181	1219	1207	1195	1183	1169	835	847	812	824	798	761	774	786	205/206	165/166
1162	1150	1138	1220	1208	1196	1184	1170	834	846	811	823	797	762	773	785	203/204	163/164
1163	1151	1139	1221	1209	1197	1185	1171	833	845	810	822	796	808	772	784	201/202	161/162
1164	1152	1140	1222	1210	1198	1186	1172	832	844	809	821	795	807	771	783	199/200	159/160';

$vagas['F'] = '1057	1069	1116	1128	1092	1104	1082	757	745	715	727	709	697	676	688	110	72/99	60/61
1056	1068	1115	1127	1090	1103	1083	758	746	734	726	710	698	675	687	111	97/98	58/59
1055	1067	1114	1126	1091	1102	1084	759	747	735	725	711	699	674	686	112	96	56/57
1054	1066	1113	1125	1136	1101	1085	760	748	736	724	712	700	673	685	113	95	54/55
1053	1065	1112	1124	1137	1100	1086	1074	749	737	723	713	701	672	684	114	94	52/53
1052	1064	1111	1123	1135	1099	1087	1075	750	738	722	714	702	671	683	695	92/93	50/51
1051	1063	1110	1122	1134	1098	1088	1076	751	739	721	733	703	670	682	694	90/91	48/49
1050	1062	1109	1121	1133	1097	1089	1077	752	740	720	732	704	669	681	693	100/101	62/63
1049	1061	1073	1120	1132	1096	1108	1078	753	741	719	731	705	668	680	692	102/103	64/65
1048	1060	1072	1119	1131	1095	1107	1079	754	742	718	730	706	667	679	691	104/105	66/67
1047	1059	1071	1118	1130	1094	1106	1080	755	743	717	729	707	666	678	690	106/107	68/69
1046	1058	1070	1117	1129	1093	1105	1081	756	744	716	728	708	696	677	689	108/109	70/71';

$vagasCompleto = 'A	101	618	Descoberta Livre	Térreo
A	102	619	Descoberta Livre	Térreo
A	103	620	Descoberta Livre	Térreo
A	104	621	Descoberta Livre	Térreo
A	105	622	Descoberta Livre	Térreo
A	106	623	Descoberta Livre	Térreo
A	107	624	Descoberta Livre	Térreo
A	108	625	Descoberta Livre	Térreo
A	109	626	Descoberta Livre	Térreo
A	110	627	Descoberta Livre	Térreo
A	111	628	Descoberta Livre	Térreo
A	112	629	Descoberta Livre	Térreo
A	201	585	Descoberta Livre	Térreo
A	202	586	Descoberta Livre	Térreo
A	203	587	Descoberta Livre	Térreo
A	204	588	Descoberta Livre	Térreo
A	205	589	Descoberta Livre	Térreo
A	206	590	Descoberta Livre	Térreo
A	207	612	Descoberta Livre	Térreo
A	208	613	Descoberta Livre	Térreo
A	209	614	Descoberta Livre	Térreo
A	210	615	Descoberta Livre	Térreo
A	211	616	Descoberta Livre	Térreo
A	212	617	Descoberta Livre	Térreo
A	301	573	Descoberta Livre	Térreo
A	302	574	Descoberta Livre	Térreo
A	303	575	Descoberta Livre	Térreo
A	304	576	Descoberta Livre	Térreo
A	305	577	Descoberta Livre	Térreo
A	306	578	Descoberta Livre	Térreo
A	307	579	Descoberta Livre	Térreo
A	308	580	Descoberta Livre	Térreo
A	309	581	Descoberta Livre	Térreo
A	310	582	Descoberta Livre	Térreo
A	311	583	Descoberta Livre	Térreo
A	312	584	Descoberta Livre	Térreo
A	401	534	Descoberta Livre	Térreo
A	402	533	Descoberta Livre	Térreo
A	403	563	Descoberta Livre	Térreo
A	404	564	Descoberta Livre	Térreo
A	405	565	Descoberta Livre	Térreo
A	406	566	Descoberta Livre	Térreo
A	407	567	Descoberta Livre	Térreo
A	408	568	Descoberta Livre	Térreo
A	409	569	Descoberta Livre	Térreo
A	410	570	Descoberta Livre	Térreo
A	411	571	Descoberta Livre	Térreo
A	412	572	Descoberta Livre	Térreo
A	501	546	Descoberta Livre	Térreo
A	502	545	Descoberta Livre	Térreo
A	503	544	Descoberta Livre	Térreo
A	504	543	Descoberta Livre	Térreo
A	505	542	Descoberta Livre	Térreo
A	506	541	Descoberta Livre	Térreo
A	507	540	Descoberta Livre	Térreo
A	508	539	Descoberta Livre	Térreo
A	509	538	Descoberta Livre	Térreo
A	510	537	Descoberta Livre	Térreo
A	511	536	Descoberta Livre	Térreo
A	512	535	Descoberta Livre	Térreo
A	601	558	Descoberta Livre	Térreo
A	602	557	Descoberta Livre	Térreo
A	603	556	Descoberta Livre	Térreo
A	604	555	Descoberta Livre	Térreo
A	605	554	Descoberta Livre	Térreo
A	606	553	Descoberta Livre	Térreo
A	607	552	Descoberta Livre	Térreo
A	608	551	Descoberta Livre	Térreo
A	609	550	Descoberta Livre	Térreo
A	610	549	Descoberta Livre	Térreo
A	611	548	Descoberta Livre	Térreo
A	612	547	Descoberta Livre	Térreo
A	701	598	Descoberta Livre	Térreo
A	702	597	Descoberta Livre	Térreo
A	703	596	Descoberta Livre	Térreo
A	704	595	Descoberta Livre	Térreo
A	705	594	Descoberta Livre	Térreo
A	706	593	Descoberta Livre	Térreo
A	707	592	Descoberta Livre	Térreo
A	708	591	Descoberta Livre	Térreo
A	709	562	Descoberta Livre	Térreo
A	710	561	Descoberta Livre	Térreo
A	711	560	Descoberta Livre	Térreo
A	712	559	Descoberta Livre	Térreo
A	801	610	Descoberta Livre	Térreo
A	802	609	Descoberta Livre	Térreo
A	803	608	Descoberta Livre	Térreo
A	804	607	Descoberta Livre	Térreo
A	805	606	Descoberta Livre	Térreo
A	806	605	Descoberta Livre	Térreo
A	807	604	Descoberta Livre	Térreo
A	808	603	Descoberta Livre	Térreo
A	809	602	Descoberta Livre	Térreo
A	810	601	Descoberta Livre	Térreo
A	811	600	Descoberta Livre	Térreo
A	812	599	Descoberta Livre	Térreo
A	901	655	Descoberta Livre	Térreo
A	902	656	Descoberta Livre	Térreo
A	903	657	Descoberta Livre	Térreo
A	904	658	Descoberta Livre	Térreo
A	905	659	Descoberta Livre	Térreo
A	906	660	Descoberta Livre	Térreo
A	907	661	Descoberta Livre	Térreo
A	908	662	Descoberta Livre	Térreo
A	909	663	Descoberta Livre	Térreo
A	910	664	Descoberta Livre	Térreo
A	911	665	Descoberta Livre	Térreo
A	912	611	Descoberta Livre	Térreo
A	1001	643	Descoberta Livre	Térreo
A	1002	644	Descoberta Livre	Térreo
A	1003	645	Descoberta Livre	Térreo
A	1004	646	Descoberta Livre	Térreo
A	1005	647	Descoberta Livre	Térreo
A	1006	648	Descoberta Livre	Térreo
A	1007	649	Descoberta Livre	Térreo
A	1008	650	Descoberta Livre	Térreo
A	1009	651	Descoberta Livre	Térreo
A	1010	652	Descoberta Livre	Térreo
A	1011	653	Descoberta Livre	Térreo
A	1012	654	Descoberta Livre	Térreo
A	1101	217	Coberta Livre	Semi-enterrado
A	1102	218	Coberta Livre	Semi-enterrado
A	1103	219	Coberta Livre	Semi-enterrado
A	1104	220	Coberta Livre	Semi-enterrado
A	1105	221	Coberta Livre	Semi-enterrado
A	1106	636	Descoberta Livre	Térreo
A	1107	637	Descoberta Livre	Térreo
A	1108	638	Descoberta Livre	Térreo
A	1109	639	Descoberta Livre	Térreo
A	1110	640	Descoberta Livre	Térreo
A	1111	641	Descoberta Livre	Térreo
A	1112	642	Descoberta Livre	Térreo
A	1201	179	Coberta Livre	Semi-enterrado
A	1202	180	Coberta Livre	Semi-enterrado
A	1203	181	Coberta Livre	Semi-enterrado
A	1204	182	Coberta Livre	Semi-enterrado
A	1205	209	Coberta Livre	Semi-enterrado
A	1206	210	Coberta Livre	Semi-enterrado
A	1207	211	Coberta Livre	Semi-enterrado
A	1208	212	Coberta Livre	Semi-enterrado
A	1209	213	Coberta Livre	Semi-enterrado
A	1210	214	Coberta Livre	Semi-enterrado
A	1211	215	Coberta Livre	Semi-enterrado
A	1212	216	Coberta Livre	Semi-enterrado
A	1301	140	Coberta Livre	Semi-enterrado
A	1302	168	Coberta Livre	Semi-enterrado
A	1303	169	Coberta Livre	Semi-enterrado
A	1304	170	Coberta Livre	Semi-enterrado
A	1305	171	Coberta Livre	Semi-enterrado
A	1306	172	Coberta Livre	Semi-enterrado
A	1307	173	Coberta Livre	Semi-enterrado
A	1308	174	Coberta Livre	Semi-enterrado
A	1309	175	Coberta Livre	Semi-enterrado
A	1310	176	Coberta Livre	Semi-enterrado
A	1311	177	Coberta Livre	Semi-enterrado
A	1312	178	Coberta Livre	Semi-enterrado
A	1401	128	Coberta Livre	Semi-enterrado
A	1402	129	Coberta Livre	Semi-enterrado
A	1403	130	Coberta Livre	Semi-enterrado
A	1404	131	Coberta Livre	Semi-enterrado
A	1405	132	Coberta Livre	Semi-enterrado
A	1406	133	Coberta Livre	Semi-enterrado
A	1407	134	Coberta Livre	Semi-enterrado
A	1408	135	Coberta Livre	Semi-enterrado
A	1409	136	Coberta Livre	Semi-enterrado
A	1410	137	Coberta Livre	Semi-enterrado
A	1411	138	Coberta Livre	Semi-enterrado
A	1412	139	Coberta Livre	Semi-enterrado
A	1501	116	Coberta Livre	Semi-enterrado
A	1502	117	Coberta Livre	Semi-enterrado
A	1503	118	Coberta Livre	Semi-enterrado
A	1504	119	Coberta Livre	Semi-enterrado
A	1505	120	Coberta Livre	Semi-enterrado
A	1506	121	Coberta Livre	Semi-enterrado
A	1507	122	Coberta Livre	Semi-enterrado
A	1508	123	Coberta Livre	Semi-enterrado
A	1509	124	Coberta Livre	Semi-enterrado
A	1510	125	Coberta Livre	Semi-enterrado
A	1511	126	Coberta Livre	Semi-enterrado
A	1512	127	Coberta Livre	Semi-enterrado
A	1601	79	Coberta Livre	Semi-enterrado
A	1602	80	Coberta Livre	Semi-enterrado
A	1603	81	Coberta Livre	Semi-enterrado
A	1604	82	Coberta Livre	Semi-enterrado
A	1605	83	Coberta Livre	Semi-enterrado
A	1606	84	Coberta Livre	Semi-enterrado
A	1607	85	Coberta Livre	Semi-enterrado
A	1608	86	Coberta Livre	Semi-enterrado
A	1609	87	Coberta Livre	Semi-enterrado
A	1610	88	Coberta Livre	Semi-enterrado
A	1611	89	Coberta Livre	Semi-enterrado
A	1612	115	Coberta Livre	Semi-enterrado
A	1701	42	Coberta Livre	Semi-enterrado
A	1702	43	Coberta Livre	Semi-enterrado
A	1703	44	Coberta Livre	Semi-enterrado
A	1704	45	Coberta Livre	Semi-enterrado
A	1705	46	Coberta Livre	Semi-enterrado
A	1706	47	Coberta Livre	Semi-enterrado
A	1707	73	Coberta Livre	Semi-enterrado
A	1708	74	Coberta Livre	Semi-enterrado
A	1709	75	Coberta Livre	Semi-enterrado
A	1710	76	Coberta Livre	Semi-enterrado
A	1711	77	Coberta Livre	Semi-enterrado
A	1712	78	Coberta Livre	Semi-enterrado
A	1801	30	Descoberta Livre	Semi-enterrado
A	1802	31	Descoberta Livre	Semi-enterrado
A	1803	32	Coberta Livre	Semi-enterrado
A	1804	33	Coberta Livre	Semi-enterrado
A	1805	34	Coberta Livre	Semi-enterrado
A	1806	35	Coberta Livre	Semi-enterrado
A	1807	36	Coberta Livre	Semi-enterrado
A	1808	37	Coberta Livre	Semi-enterrado
A	1809	38	Coberta Livre	Semi-enterrado
A	1810	39	Coberta Livre	Semi-enterrado
A	1811	40	Coberta Livre	Semi-enterrado
A	1812	41	Coberta Livre	Semi-enterrado
A	1901	630	Descoberta Livre	Térreo
A	1901	631	Descoberta Livre	Térreo
A	1902	632	Descoberta Livre	Térreo
A	1902	633	Descoberta Livre	Térreo
A	1903	634	Descoberta Livre	Térreo
A	1903	635	Descoberta Livre	Térreo
A	1904	12	Descoberta Livre	Semi-enterrado
A	1904	13	Descoberta Livre	Semi-enterrado
A	1905	14	Descoberta Livre	Semi-enterrado
A	1905	15	Descoberta Livre	Semi-enterrado
A	1906	16	Descoberta Livre	Semi-enterrado
A	1906	17	Descoberta Livre	Semi-enterrado
A	1907	18	Descoberta Livre	Semi-enterrado
A	1907	19	Descoberta Livre	Semi-enterrado
A	1908	20	Descoberta Livre	Semi-enterrado
A	1908	21	Descoberta Livre	Semi-enterrado
A	1909	22	Descoberta Livre	Semi-enterrado
A	1909	23	Descoberta Livre	Semi-enterrado
A	1910	24	Descoberta Livre	Semi-enterrado
A	1910	25	Descoberta Livre	Semi-enterrado
A	1911	26	Descoberta Livre	Semi-enterrado
A	1911	27	Descoberta Livre	Semi-enterrado
A	1912	28	Descoberta Livre	Semi-enterrado
A	1912	29	Descoberta Livre	Semi-enterrado
B	101	521	Descoberta Livre	Térreo
B	102	522	Descoberta Livre	Térreo
B	103	523	Descoberta Livre	Térreo
B	104	524	Descoberta Livre	Térreo
B	105	525	Descoberta Livre	Térreo
B	106	526	Descoberta Livre	Térreo
B	107	527	Descoberta Livre	Térreo
B	108	528	Descoberta Livre	Térreo
B	109	529	Descoberta Livre	Térreo
B	110	530	Descoberta Livre	Térreo
B	111	531	Descoberta Livre	Térreo
B	112	532	Descoberta Livre	Térreo
B	201	509	Descoberta Livre	Térreo
B	202	510	Descoberta Livre	Térreo
B	203	511	Descoberta Livre	Térreo
B	204	512	Descoberta Livre	Térreo
B	205	513	Descoberta Livre	Térreo
B	206	514	Descoberta Livre	Térreo
B	207	515	Descoberta Livre	Térreo
B	208	516	Descoberta Livre	Térreo
B	209	517	Descoberta Livre	Térreo
B	210	518	Descoberta Livre	Térreo
B	211	519	Descoberta Livre	Térreo
B	212	520	Descoberta Livre	Térreo
B	301	497	Descoberta Livre	Térreo
B	302	498	Descoberta Livre	Térreo
B	303	499	Descoberta Livre	Térreo
B	304	500	Descoberta Livre	Térreo
B	305	501	Descoberta Livre	Térreo
B	306	502	Descoberta Livre	Térreo
B	307	503	Descoberta Livre	Térreo
B	308	504	Descoberta Livre	Térreo
B	309	505	Descoberta Livre	Térreo
B	310	506	Descoberta Livre	Térreo
B	311	507	Descoberta Livre	Térreo
B	312	508	Descoberta Livre	Térreo
B	401	485	Descoberta Livre	Térreo
B	402	486	Descoberta Livre	Térreo
B	403	487	Descoberta Livre	Térreo
B	404	488	Descoberta Livre	Térreo
B	405	489	Descoberta Livre	Térreo
B	406	490	Descoberta Livre	Térreo
B	407	491	Descoberta Livre	Térreo
B	408	492	Descoberta Livre	Térreo
B	409	493	Descoberta Livre	Térreo
B	410	494	Descoberta Livre	Térreo
B	411	495	Descoberta Livre	Térreo
B	412	496	Descoberta Livre	Térreo
B	501	473	Descoberta Livre	Térreo
B	502	474	Descoberta Livre	Térreo
B	503	475	Descoberta Livre	Térreo
B	504	476	Descoberta Livre	Térreo
B	505	477	Descoberta Livre	Térreo
B	506	478	Descoberta Livre	Térreo
B	507	479	Descoberta Livre	Térreo
B	508	480	Descoberta Livre	Térreo
B	509	481	Descoberta Livre	Térreo
B	510	482	Descoberta Livre	Térreo
B	511	483	Descoberta Livre	Térreo
B	512	484	Descoberta Livre	Térreo
B	601	430	Descoberta Livre	Térreo
B	602	431	Descoberta Livre	Térreo
B	603	432	Descoberta Livre	Térreo
B	604	433	Descoberta Livre	Térreo
B	605	434	Descoberta Livre	Térreo
B	606	435	Descoberta Livre	Térreo
B	607	467	Descoberta Livre	Térreo
B	608	468	Descoberta Livre	Térreo
B	609	469	Descoberta Livre	Térreo
B	610	470	Descoberta Livre	Térreo
B	611	471	Descoberta Livre	Térreo
B	612	472	Descoberta Livre	Térreo
B	701	418	Descoberta Livre	Térreo
B	702	419	Descoberta Livre	Térreo
B	703	420	Descoberta Livre	Térreo
B	704	421	Descoberta Livre	Térreo
B	705	422	Descoberta Livre	Térreo
B	706	423	Descoberta Livre	Térreo
B	707	424	Descoberta Livre	Térreo
B	708	425	Descoberta Livre	Térreo
B	709	426	Descoberta Livre	Térreo
B	710	427	Descoberta Livre	Térreo
B	711	428	Descoberta Livre	Térreo
B	712	429	Descoberta Livre	Térreo
B	801	5	Descoberta Livre	Semi-enterrado
B	802	4	Descoberta Livre	Semi-enterrado
B	803	3	Descoberta Livre	Semi-enterrado
B	804	2	Descoberta Livre	Semi-enterrado
B	805	1	Descoberta Livre	Semi-enterrado
B	806	411	Descoberta Livre	Térreo
B	807	412	Descoberta Livre	Térreo
B	808	413	Descoberta Livre	Térreo
B	809	414	Descoberta Livre	Térreo
B	810	415	Descoberta Livre	Térreo
B	811	416	Descoberta Livre	Térreo
B	812	417	Descoberta Livre	Térreo
B	901	227	Coberta Livre	Semi-enterrado
B	902	226	Coberta Livre	Semi-enterrado
B	903	225	Coberta Livre	Semi-enterrado
B	904	224	Coberta Livre	Semi-enterrado
B	905	223	Coberta Livre	Semi-enterrado
B	906	222	Coberta Livre	Semi-enterrado
B	907	11	Descoberta Livre	Semi-enterrado
B	908	10	Descoberta Livre	Semi-enterrado
B	909	9	Descoberta Livre	Semi-enterrado
B	910	8	Descoberta Livre	Semi-enterrado
B	911	7	Descoberta Livre	Semi-enterrado
B	912	6	Descoberta Livre	Semi-enterrado
B	1001	265	Coberta Livre	Semi-enterrado
B	1002	264	Coberta Livre	Semi-enterrado
B	1003	263	Coberta Livre	Semi-enterrado
B	1004	262	Coberta Livre	Semi-enterrado
B	1005	261	Coberta Livre	Semi-enterrado
B	1006	234	Coberta Livre	Semi-enterrado
B	1007	233	Coberta Livre	Semi-enterrado
B	1008	232	Coberta Livre	Semi-enterrado
B	1009	231	Coberta Livre	Semi-enterrado
B	1010	230	Coberta Livre	Semi-enterrado
B	1011	229	Coberta Livre	Semi-enterrado
B	1012	228	Coberta Livre	Semi-enterrado
B	1101	277	Coberta Livre	Semi-enterrado
B	1102	276	Coberta Livre	Semi-enterrado
B	1103	275	Coberta Livre	Semi-enterrado
B	1104	274	Coberta Livre	Semi-enterrado
B	1105	273	Coberta Livre	Semi-enterrado
B	1106	272	Coberta Livre	Semi-enterrado
B	1107	271	Coberta Livre	Semi-enterrado
B	1108	270	Coberta Livre	Semi-enterrado
B	1109	269	Coberta Livre	Semi-enterrado
B	1110	268	Coberta Livre	Semi-enterrado
B	1111	267	Coberta Livre	Semi-enterrado
B	1112	266	Coberta Livre	Semi-enterrado
B	1201	308	Coberta Livre	Semi-enterrado
B	1202	307	Coberta Livre	Semi-enterrado
B	1203	306	Coberta Livre	Semi-enterrado
B	1204	305	Coberta Livre	Semi-enterrado
B	1205	304	Coberta Livre	Semi-enterrado
B	1206	303	Coberta Livre	Semi-enterrado
B	1207	302	Coberta Livre	Semi-enterrado
B	1208	301	Coberta Livre	Semi-enterrado
B	1209	300	Coberta Livre	Semi-enterrado
B	1210	299	Coberta Livre	Semi-enterrado
B	1211	298	Coberta Livre	Semi-enterrado
B	1212	278	Coberta Livre	Semi-enterrado
B	1301	320	Coberta Livre	Semi-enterrado
B	1302	319	Coberta Livre	Semi-enterrado
B	1303	318	Coberta Livre	Semi-enterrado
B	1304	317	Coberta Livre	Semi-enterrado
B	1305	316	Coberta Livre	Semi-enterrado
B	1306	315	Coberta Livre	Semi-enterrado
B	1307	314	Coberta Livre	Semi-enterrado
B	1308	313	Coberta Livre	Semi-enterrado
B	1309	312	Coberta Livre	Semi-enterrado
B	1310	311	Coberta Livre	Semi-enterrado
B	1311	310	Coberta Livre	Semi-enterrado
B	1312	309	Coberta Livre	Semi-enterrado
B	1401	332	Coberta Livre	Semi-enterrado
B	1402	331	Coberta Livre	Semi-enterrado
B	1403	330	Coberta Livre	Semi-enterrado
B	1404	329	Coberta Livre	Semi-enterrado
B	1405	328	Coberta Livre	Semi-enterrado
B	1406	327	Coberta Livre	Semi-enterrado
B	1407	326	Coberta Livre	Semi-enterrado
B	1408	325	Coberta Livre	Semi-enterrado
B	1409	324	Coberta Livre	Semi-enterrado
B	1410	323	Coberta Livre	Semi-enterrado
B	1411	322	Coberta Livre	Semi-enterrado
B	1412	321	Coberta Livre	Semi-enterrado
B	1501	362	Coberta Livre	Semi-enterrado
B	1502	361	Coberta Livre	Semi-enterrado
B	1503	360	Coberta Livre	Semi-enterrado
B	1504	359	Coberta Livre	Semi-enterrado
B	1505	358	Coberta Livre	Semi-enterrado
B	1506	357	Coberta Livre	Semi-enterrado
B	1507	356	Coberta Livre	Semi-enterrado
B	1508	355	Coberta Livre	Semi-enterrado
B	1509	354	Coberta Livre	Semi-enterrado
B	1510	353	Coberta Livre	Semi-enterrado
B	1511	352	Coberta Livre	Semi-enterrado
B	1512	333	Coberta Livre	Semi-enterrado
B	1601	393	Coberta Livre	Semi-enterrado
B	1602	373	Coberta Livre	Semi-enterrado
B	1603	372	Coberta Livre	Semi-enterrado
B	1604	371	Coberta Livre	Semi-enterrado
B	1605	370	Coberta Livre	Semi-enterrado
B	1606	369	Coberta Livre	Semi-enterrado
B	1607	368	Coberta Livre	Semi-enterrado
B	1608	367	Coberta Livre	Semi-enterrado
B	1609	366	Coberta Livre	Semi-enterrado
B	1610	365	Coberta Livre	Semi-enterrado
B	1611	364	Coberta Livre	Semi-enterrado
B	1612	363	Coberta Livre	Semi-enterrado
B	1701	405	Coberta Livre	Semi-enterrado
B	1702	404	Coberta Livre	Semi-enterrado
B	1703	403	Coberta Livre	Semi-enterrado
B	1704	402	Coberta Livre	Semi-enterrado
B	1705	401	Coberta Livre	Semi-enterrado
B	1706	400	Coberta Livre	Semi-enterrado
B	1707	399	Coberta Livre	Semi-enterrado
B	1708	398	Coberta Livre	Semi-enterrado
B	1709	397	Coberta Livre	Semi-enterrado
B	1710	396	Coberta Livre	Semi-enterrado
B	1711	395	Coberta Livre	Semi-enterrado
B	1712	394	Coberta Livre	Semi-enterrado
B	1801	444	Coberta Livre	Térreo
B	1802	445	Coberta Livre	Térreo
B	1803	446	Coberta Livre	Térreo
B	1804	447	Coberta Livre	Térreo
B	1805	448	Coberta Livre	Térreo
B	1806	449	Coberta Livre	Térreo
B	1807	450	Coberta Livre	Térreo
B	1808	410	Coberta Livre	Semi-enterrado
B	1809	409	Coberta Livre	Semi-enterrado
B	1810	408	Coberta Livre	Semi-enterrado
B	1811	407	Coberta Livre	Semi-enterrado
B	1812	406	Coberta Livre	Semi-enterrado
B	1901	451	Coberta Livre	Térreo
B	1901	452	Coberta Livre	Térreo
B	1902	453	Coberta Livre	Térreo
B	1902	454	Coberta Livre	Térreo
B	1903	455	Coberta Livre	Térreo
B	1903	456	Coberta Livre	Térreo
B	1904	457	Coberta Livre	Térreo
B	1904	458	Coberta Livre	Térreo
B	1905	459	Coberta Livre	Térreo
B	1905	460	Coberta Livre	Térreo
B	1906	461	Coberta Livre	Térreo
B	1906	462	Coberta Livre	Térreo
B	1907	463	Coberta Livre	Térreo
B	1907	464	Coberta Livre	Térreo
B	1908	465	Coberta Livre	Térreo
B	1908	466	Descoberta Livre	Térreo
B	1909	436	Descoberta Livre	Térreo
B	1909	437	Coberta Livre	Térreo
B	1910	438	Coberta Livre	Térreo
B	1910	439	Coberta Livre	Térreo
B	1911	440	Coberta Livre	Térreo
B	1911	441	Coberta Livre	Térreo
B	1912	442	Coberta Livre	Térreo
B	1912	443	Coberta Livre	Térreo
C	101	1395	Descoberta Livre	Mezzanino
C	102	1394	Descoberta Livre	Mezzanino
C	103	1393	Descoberta Livre	Mezzanino
C	104	1392	Descoberta Livre	Mezzanino
C	105	1391	Descoberta Livre	Mezzanino
C	106	1390	Descoberta Livre	Mezzanino
C	107	1389	Descoberta Livre	Mezzanino
C	108	1388	Descoberta Livre	Mezzanino
C	109	1387	Descoberta Livre	Mezzanino
C	110	1386	Descoberta Livre	Mezzanino
C	111	1385	Descoberta Livre	Mezzanino
C	112	1384	Descoberta Livre	Mezzanino
C	201	1407	Descoberta Livre	Mezzanino
C	202	1406	Descoberta Livre	Mezzanino
C	203	1405	Descoberta Livre	Mezzanino
C	204	1404	Descoberta Livre	Mezzanino
C	205	1403	Descoberta Livre	Mezzanino
C	206	1402	Descoberta Livre	Mezzanino
C	207	1401	Descoberta Livre	Mezzanino
C	208	1400	Descoberta Livre	Mezzanino
C	209	1399	Descoberta Livre	Mezzanino
C	210	1398	Descoberta Livre	Mezzanino
C	211	1397	Descoberta Livre	Mezzanino
C	212	1396	Descoberta Livre	Mezzanino
C	301	1369	Descoberta Livre	Mezzanino
C	302	1364	Descoberta Livre	Mezzanino
C	303	1365	Descoberta Livre	Mezzanino
C	304	1366	Descoberta Livre	Mezzanino
C	305	1367	Descoberta Livre	Mezzanino
C	306	1368	Descoberta Livre	Mezzanino
C	307	1413	Descoberta Livre	Mezzanino
C	308	1412	Descoberta Livre	Mezzanino
C	309	1411	Descoberta Livre	Mezzanino
C	310	1410	Descoberta Livre	Mezzanino
C	311	1409	Descoberta Livre	Mezzanino
C	312	1408	Descoberta Livre	Mezzanino
C	401	1381	Coberta Livre	Mezzanino
C	402	1380	Coberta Livre	Mezzanino
C	403	1379	Coberta Livre	Mezzanino
C	404	1378	Coberta Livre	Mezzanino
C	405	1377	Coberta Livre	Mezzanino
C	406	1376	Coberta Livre	Mezzanino
C	407	1375	Coberta Livre	Mezzanino
C	408	1374	Coberta Livre	Mezzanino
C	409	1373	Coberta Livre	Mezzanino
C	410	1372	Coberta Livre	Mezzanino
C	411	1371	Coberta Livre	Mezzanino
C	412	1370	Coberta Livre	Mezzanino
C	501	1357	Coberta Livre	Mezzanino
C	502	1356	Coberta Livre	Mezzanino
C	503	1355	Coberta Livre	Mezzanino
C	504	1354	Coberta Livre	Mezzanino
C	505	1353	Coberta Livre	Mezzanino
C	506	1352	Coberta Livre	Mezzanino
C	507	1351	Coberta Livre	Mezzanino
C	508	1350	Coberta Livre	Mezzanino
C	509	1349	Coberta Livre	Mezzanino
C	510	1348	Descoberta Livre	Mezzanino
C	511	1383	Descoberta Livre	Mezzanino
C	512	1382	Coberta Livre	Mezzanino
C	601	1327	Descoberta Livre	Mezzanino
C	602	1326	Descoberta Livre	Mezzanino
C	603	1325	Descoberta Livre	Mezzanino
C	604	1324	Descoberta Livre	Mezzanino
C	605	1323	Descoberta Livre	Mezzanino
C	606	1322	Descoberta Livre	Mezzanino
C	607	1363	Descoberta Livre	Mezzanino
C	608	1362	Coberta Livre	Mezzanino
C	609	1361	Coberta Livre	Mezzanino
C	610	1360	Coberta Livre	Mezzanino
C	611	1359	Coberta Livre	Mezzanino
C	612	1358	Coberta Livre	Mezzanino
C	701	1339	Descoberta Livre	Mezzanino
C	702	1338	Descoberta Livre	Mezzanino
C	703	1337	Descoberta Livre	Mezzanino
C	704	1336	Descoberta Livre	Mezzanino
C	705	1335	Descoberta Livre	Mezzanino
C	706	1334	Descoberta Livre	Mezzanino
C	707	1333	Descoberta Livre	Mezzanino
C	708	1332	Descoberta Livre	Mezzanino
C	709	1331	Descoberta Livre	Mezzanino
C	710	1330	Descoberta Livre	Mezzanino
C	711	1329	Descoberta Livre	Mezzanino
C	712	1328	Descoberta Livre	Mezzanino
C	801	1019	Coberta Livre	Mezzanino
C	802	1018	Coberta Livre	Mezzanino
C	803	1017	Coberta Livre	Mezzanino
C	804	1016	Coberta Livre	Mezzanino
C	805	1347	Descoberta Livre	Mezzanino
C	806	1346	Descoberta Livre	Mezzanino
C	807	1345	Descoberta Livre	Mezzanino
C	808	1344	Descoberta Livre	Mezzanino
C	809	1343	Descoberta Livre	Mezzanino
C	810	1342	Descoberta Livre	Mezzanino
C	811	1341	Descoberta Livre	Mezzanino
C	812	1340	Descoberta Livre	Mezzanino
C	901	1031	Coberta Livre	Mezzanino
C	902	1030	Coberta Livre	Mezzanino
C	903	1029	Coberta Livre	Mezzanino
C	904	1028	Coberta Livre	Mezzanino
C	905	1027	Coberta Livre	Mezzanino
C	906	1026	Coberta Livre	Mezzanino
C	907	1025	Coberta Livre	Mezzanino
C	908	1024	Coberta Livre	Mezzanino
C	909	1023	Coberta Livre	Mezzanino
C	910	1022	Coberta Livre	Mezzanino
C	911	1021	Coberta Livre	Mezzanino
C	912	1020	Coberta Livre	Mezzanino
C	1001	1043	Coberta Livre	Mezzanino
C	1002	1042	Coberta Livre	Mezzanino
C	1003	1041	Coberta Livre	Mezzanino
C	1004	1040	Coberta Livre	Mezzanino
C	1005	1039	Coberta Livre	Mezzanino
C	1006	1038	Coberta Livre	Mezzanino
C	1007	1037	Coberta Livre	Mezzanino
C	1008	1036	Coberta Livre	Mezzanino
C	1009	1035	Coberta Livre	Mezzanino
C	1010	1034	Coberta Livre	Mezzanino
C	1011	1033	Coberta Livre	Mezzanino
C	1012	1032	Coberta Livre	Mezzanino
C	1101	1006	Coberta Livre	Mezzanino
C	1102	1005	Coberta Livre	Mezzanino
C	1103	1004	Coberta Livre	Mezzanino
C	1104	1003	Coberta Livre	Mezzanino
C	1105	1002	Coberta Livre	Mezzanino
C	1106	1001	Coberta Livre	Mezzanino
C	1107	1000	Coberta Livre	Mezzanino
C	1108	999	Coberta Livre	Mezzanino
C	1109	998	Coberta Livre	Mezzanino
C	1110	997	Coberta Livre	Mezzanino
C	1111	1045	Coberta Livre	Mezzanino
C	1112	1044	Coberta Livre	Mezzanino
C	1201	980	Coberta Livre	Mezzanino
C	1202	979	Coberta Livre	Mezzanino
C	1203	978	Coberta Livre	Mezzanino
C	1204	1015	Coberta Livre	Mezzanino
C	1205	1014	Coberta Livre	Mezzanino
C	1206	1013	Coberta Livre	Mezzanino
C	1207	1012	Coberta Livre	Mezzanino
C	1208	1011	Coberta Livre	Mezzanino
C	1209	1010	Coberta Livre	Mezzanino
C	1210	1009	Coberta Livre	Mezzanino
C	1211	1008	Coberta Livre	Mezzanino
C	1212	1007	Coberta Livre	Mezzanino
C	1301	992	Coberta Livre	Mezzanino
C	1302	991	Coberta Livre	Mezzanino
C	1303	990	Coberta Livre	Mezzanino
C	1304	989	Coberta Livre	Mezzanino
C	1305	988	Coberta Livre	Mezzanino
C	1306	987	Coberta Livre	Mezzanino
C	1307	986	Coberta Livre	Mezzanino
C	1308	985	Coberta Livre	Mezzanino
C	1309	984	Coberta Livre	Mezzanino
C	1310	983	Coberta Livre	Mezzanino
C	1311	982	Coberta Livre	Mezzanino
C	1312	981	Coberta Livre	Mezzanino
C	1401	958	Coberta Livre	Mezzanino
C	1402	957	Coberta Livre	Mezzanino
C	1403	956	Coberta Livre	Mezzanino
C	1404	955	Coberta Livre	Mezzanino
C	1405	954	Coberta Livre	Mezzanino
C	1406	953	Coberta Livre	Mezzanino
C	1407	952	Coberta Livre	Mezzanino
C	1408	951	Coberta Livre	Mezzanino
C	1409	996	Coberta Livre	Mezzanino
C	1410	995	Coberta Livre	Mezzanino
C	1411	994	Coberta Livre	Mezzanino
C	1412	993	Coberta Livre	Mezzanino
C	1501	970	Coberta Livre	Mezzanino
C	1502	969	Coberta Livre	Mezzanino
C	1503	968	Coberta Livre	Mezzanino
C	1504	967	Coberta Livre	Mezzanino
C	1505	966	Coberta Livre	Mezzanino
C	1506	965	Coberta Livre	Mezzanino
C	1507	964	Coberta Livre	Mezzanino
C	1508	963	Coberta Livre	Mezzanino
C	1509	962	Coberta Livre	Mezzanino
C	1510	961	Coberta Livre	Mezzanino
C	1511	960	Coberta Livre	Mezzanino
C	1512	959	Coberta Livre	Mezzanino
C	1601	388	Coberta Livre	Semi-enterrado
C	1602	389	Coberta Livre	Semi-enterrado
C	1603	390	Coberta Livre	Semi-enterrado
C	1604	391	Coberta Livre	Semi-enterrado
C	1605	392	Coberta Livre	Semi-enterrado
C	1606	977	Coberta Livre	Térreo
C	1607	976	Coberta Livre	Térreo
C	1608	975	Coberta Livre	Térreo
C	1609	974	Coberta Livre	Térreo
C	1610	973	Coberta Livre	Térreo
C	1611	972	Coberta Livre	Térreo
C	1612	971	Coberta Livre	Térreo
C	1701	376	Coberta Livre	Semi-enterrado
C	1702	377	Coberta Livre	Semi-enterrado
C	1703	378	Coberta Livre	Semi-enterrado
C	1704	379	Coberta Livre	Semi-enterrado
C	1705	380	Coberta Livre	Semi-enterrado
C	1706	381	Coberta Livre	Semi-enterrado
C	1707	382	Coberta Livre	Semi-enterrado
C	1708	383	Coberta Livre	Semi-enterrado
C	1709	384	Coberta Livre	Semi-enterrado
C	1710	385	Coberta Livre	Semi-enterrado
C	1711	386	Coberta Livre	Semi-enterrado
C	1712	387	Coberta Livre	Semi-enterrado
C	1801	294	Coberta Livre	Semi-enterrado
C	1801	295	Coberta Livre	Semi-enterrado
C	1802	296	Coberta Livre	Semi-enterrado
C	1802	297	Coberta Livre	Semi-enterrado
C	1803	334	Coberta Livre	Semi-enterrado
C	1803	335	Coberta Livre	Semi-enterrado
C	1804	336	Coberta Livre	Semi-enterrado
C	1804	337	Coberta Livre	Semi-enterrado
C	1805	338	Coberta Livre	Semi-enterrado
C	1805	339	Coberta Livre	Semi-enterrado
C	1806	340	Coberta Livre	Semi-enterrado
C	1806	341	Coberta Livre	Semi-enterrado
C	1807	342	Coberta Livre	Semi-enterrado
C	1807	343	Coberta Livre	Semi-enterrado
C	1808	344	Coberta Livre	Semi-enterrado
C	1808	345	Coberta Livre	Semi-enterrado
C	1809	346	Coberta Livre	Semi-enterrado
C	1809	347	Coberta Livre	Semi-enterrado
C	1810	348	Coberta Livre	Semi-enterrado
C	1810	349	Coberta Livre	Semi-enterrado
C	1811	350	Coberta Livre	Semi-enterrado
C	1811	351	Coberta Livre	Semi-enterrado
C	1812	374	Coberta Livre	Semi-enterrado
C	1812	375	Coberta Livre	Semi-enterrado
D	101	1244	Descoberta Livre	Mezzanino
D	102	1245	Descoberta Livre	Mezzanino
D	103	1246	Descoberta Livre	Mezzanino
D	104	1247	Descoberta Livre	Mezzanino
D	105	1248	Descoberta Livre	Mezzanino
D	106	1249	Descoberta Livre	Mezzanino
D	107	1250	Descoberta Livre	Mezzanino
D	108	1251	Descoberta Livre	Mezzanino
D	109	1252	Descoberta Livre	Mezzanino
D	110	1253	Descoberta Livre	Mezzanino
D	111	1254	Descoberta Livre	Mezzanino
D	112	1255	Descoberta Livre	Mezzanino
D	201	1232	Descoberta Livre	Mezzanino
D	202	1233	Descoberta Livre	Mezzanino
D	203	1234	Descoberta Livre	Mezzanino
D	204	1235	Descoberta Livre	Mezzanino
D	205	1236	Descoberta Livre	Mezzanino
D	206	1237	Descoberta Livre	Mezzanino
D	207	1238	Descoberta Livre	Mezzanino
D	208	1239	Descoberta Livre	Mezzanino
D	209	1240	Descoberta Livre	Mezzanino
D	210	1241	Descoberta Livre	Mezzanino
D	211	1242	Descoberta Livre	Mezzanino
D	212	1243	Descoberta Livre	Mezzanino
D	301	1317	Descoberta Livre	Mezzanino
D	302	1318	Descoberta Livre	Mezzanino
D	303	1319	Descoberta Livre	Mezzanino
D	304	1320	Descoberta Livre	Mezzanino
D	305	1321	Descoberta Livre	Mezzanino
D	306	1276	Descoberta Livre	Mezzanino
D	307	1275	Descoberta Livre	Mezzanino
D	308	1274	Descoberta Livre	Mezzanino
D	309	1273	Descoberta Livre	Mezzanino
D	310	1272	Descoberta Livre	Mezzanino
D	311	1230	Descoberta Livre	Mezzanino
D	312	1231	Descoberta Livre	Mezzanino
D	401	1305	Descoberta Livre	Mezzanino
D	402	1306	Descoberta Livre	Mezzanino
D	403	1307	Descoberta Livre	Mezzanino
D	404	1308	Descoberta Livre	Mezzanino
D	405	1309	Descoberta Livre	Mezzanino
D	406	1310	Descoberta Livre	Mezzanino
D	407	1311	Descoberta Livre	Mezzanino
D	408	1312	Descoberta Livre	Mezzanino
D	409	1313	Descoberta Livre	Mezzanino
D	410	1314	Descoberta Livre	Mezzanino
D	411	1315	Descoberta Livre	Mezzanino
D	412	1316	Descoberta Livre	Mezzanino
D	501	1293	Descoberta Livre	Mezzanino
D	502	1294	Descoberta Livre	Mezzanino
D	503	1295	Descoberta Livre	Mezzanino
D	504	1296	Descoberta Livre	Mezzanino
D	505	1297	Descoberta Livre	Mezzanino
D	506	1298	Descoberta Livre	Mezzanino
D	507	1299	Descoberta Livre	Mezzanino
D	508	1300	Descoberta Livre	Mezzanino
D	509	1301	Descoberta Livre	Mezzanino
D	510	1302	Descoberta Livre	Mezzanino
D	511	1303	Descoberta Livre	Mezzanino
D	512	1304	Descoberta Livre	Mezzanino
D	601	1281	Coberta Livre	Mezzanino
D	602	1282	Coberta Livre	Mezzanino
D	603	1283	Coberta Livre	Mezzanino
D	604	1284	Coberta Livre	Mezzanino
D	605	1285	Coberta Livre	Mezzanino
D	606	1286	Coberta Livre	Mezzanino
D	607	1287	Coberta Livre	Mezzanino
D	608	1288	Coberta Livre	Mezzanino
D	609	1289	Coberta Livre	Mezzanino
D	610	1290	Coberta Livre	Mezzanino
D	611	1291	Descoberta Livre	Mezzanino
D	612	1292	Descoberta Livre	Mezzanino
D	701	1264	Coberta Livre	Mezzanino
D	702	1265	Coberta Livre	Mezzanino
D	703	1266	Coberta Livre	Mezzanino
D	704	1267	Coberta Livre	Mezzanino
D	705	1268	Coberta Livre	Mezzanino
D	706	1269	Coberta Livre	Mezzanino
D	707	1270	Coberta Livre	Mezzanino
D	708	1271	Descoberta Livre	Mezzanino
D	709	1277	Descoberta Livre	Mezzanino
D	710	1278	Coberta Livre	Mezzanino
D	711	1279	Coberta Livre	Mezzanino
D	712	1280	Coberta Livre	Mezzanino
D	801	925	Coberta Livre	Térreo
D	802	924	Coberta Livre	Térreo
D	803	923	Coberta Livre	Térreo
D	804	922	Coberta Livre	Térreo
D	805	1256	Descoberta Livre	Mezzanino
D	806	1257	Coberta Livre	Mezzanino
D	807	1258	Coberta Livre	Mezzanino
D	808	1259	Coberta Livre	Mezzanino
D	809	1260	Coberta Livre	Mezzanino
D	810	1261	Coberta Livre	Mezzanino
D	811	1262	Coberta Livre	Mezzanino
D	812	1263	Coberta Livre	Mezzanino
D	901	937	Coberta Livre	Térreo
D	902	936	Coberta Livre	Térreo
D	903	935	Coberta Livre	Térreo
D	904	934	Coberta Livre	Térreo
D	905	933	Coberta Livre	Térreo
D	906	932	Coberta Livre	Térreo
D	907	931	Coberta Livre	Térreo
D	908	930	Coberta Livre	Térreo
D	909	929	Coberta Livre	Térreo
D	910	928	Coberta Livre	Térreo
D	911	927	Coberta Livre	Térreo
D	912	926	Coberta Livre	Térreo
D	1001	950	Coberta Livre	Térreo
D	1002	948	Coberta Livre	Térreo
D	1003	947	Coberta Livre	Térreo
D	1004	946	Coberta Livre	Térreo
D	1005	945	Coberta Livre	Térreo
D	1006	944	Coberta Livre	Térreo
D	1007	943	Coberta Livre	Térreo
D	1008	942	Coberta Livre	Térreo
D	1009	941	Coberta Livre	Térreo
D	1010	940	Coberta Livre	Térreo
D	1011	939	Coberta Livre	Térreo
D	1012	938	Coberta Livre	Térreo
D	1101	913	Coberta Livre	Térreo
D	1102	912	Coberta Livre	Térreo
D	1103	911	Coberta Livre	Térreo
D	1104	910	Coberta Livre	Térreo
D	1105	909	Coberta Livre	Térreo
D	1106	908	Coberta Livre	Térreo
D	1107	907	Coberta Livre	Térreo
D	1108	906	Coberta Livre	Térreo
D	1109	905	Coberta Livre	Térreo
D	1110	904	Coberta Livre	Térreo
D	1111	903	Coberta Livre	Térreo
D	1112	949	Coberta Livre	Térreo
D	1201	887	Coberta Livre	Térreo
D	1202	886	Coberta Livre	Térreo
D	1203	885	Coberta Livre	Térreo
D	1204	884	Coberta Livre	Térreo
D	1205	921	Coberta Livre	Térreo
D	1206	920	Coberta Livre	Térreo
D	1207	919	Coberta Livre	Térreo
D	1208	918	Coberta Livre	Térreo
D	1209	917	Coberta Livre	Térreo
D	1210	916	Coberta Livre	Térreo
D	1211	915	Coberta Livre	Térreo
D	1212	914	Coberta Livre	Térreo
D	1301	899	Coberta Livre	Térreo
D	1302	898	Coberta Livre	Térreo
D	1303	897	Coberta Livre	Térreo
D	1304	896	Coberta Livre	Térreo
D	1305	895	Coberta Livre	Térreo
D	1306	894	Coberta Livre	Térreo
D	1307	893	Coberta Livre	Térreo
D	1308	892	Coberta Livre	Térreo
D	1309	891	Coberta Livre	Térreo
D	1310	890	Coberta Livre	Térreo
D	1311	889	Coberta Livre	Térreo
D	1312	888	Coberta Livre	Térreo
D	1401	864	Coberta Livre	Térreo
D	1402	863	Coberta Livre	Térreo
D	1403	862	Coberta Livre	Térreo
D	1404	861	Coberta Livre	Térreo
D	1405	860	Coberta Livre	Térreo
D	1406	859	Coberta Livre	Térreo
D	1407	858	Coberta Livre	Térreo
D	1408	857	Coberta Livre	Térreo
D	1409	856	Coberta Livre	Térreo
D	1410	902	Coberta Livre	Térreo
D	1411	901	Coberta Livre	Térreo
D	1412	900	Coberta Livre	Térreo
D	1501	876	Coberta Livre	Térreo
D	1502	875	Coberta Livre	Térreo
D	1503	874	Coberta Livre	Térreo
D	1504	873	Coberta Livre	Térreo
D	1505	872	Coberta Livre	Térreo
D	1506	871	Coberta Livre	Térreo
D	1507	870	Coberta Livre	Térreo
D	1508	869	Coberta Livre	Térreo
D	1509	868	Coberta Livre	Térreo
D	1510	867	Coberta Livre	Térreo
D	1511	866	Coberta Livre	Térreo
D	1512	865	Coberta Livre	Térreo
D	1601	292	Coberta Livre	Semi-enterrado
D	1602	291	Coberta Livre	Semi-enterrado
D	1603	290	Coberta Livre	Semi-enterrado
D	1604	289	Coberta Livre	Semi-enterrado
D	1605	288	Coberta Livre	Semi-enterrado
D	1606	883	Coberta Livre	Térreo
D	1607	882	Coberta Livre	Térreo
D	1608	881	Coberta Livre	Térreo
D	1609	880	Coberta Livre	Térreo
D	1610	879	Coberta Livre	Térreo
D	1611	878	Coberta Livre	Térreo
D	1612	877	Coberta Livre	Térreo
D	1701	251	Coberta Livre	Semi-enterrado
D	1702	250	Coberta Livre	Semi-enterrado
D	1703	287	Coberta Livre	Semi-enterrado
D	1704	286	Coberta Livre	Semi-enterrado
D	1705	285	Coberta Livre	Semi-enterrado
D	1706	284	Coberta Livre	Semi-enterrado
D	1707	283	Coberta Livre	Semi-enterrado
D	1708	282	Coberta Livre	Semi-enterrado
D	1709	281	Coberta Livre	Semi-enterrado
D	1710	280	Coberta Livre	Semi-enterrado
D	1711	279	Coberta Livre	Semi-enterrado
D	1712	293	Coberta Livre	Semi-enterrado
D	1801	248	Coberta Livre	Semi-enterrado
D	1801	249	Coberta Livre	Semi-enterrado
D	1802	246	Coberta Livre	Semi-enterrado
D	1802	247	Coberta Livre	Semi-enterrado
D	1803	244	Coberta Livre	Semi-enterrado
D	1803	245	Coberta Livre	Semi-enterrado
D	1804	242	Coberta Livre	Semi-enterrado
D	1804	243	Coberta Livre	Semi-enterrado
D	1805	240	Coberta Livre	Semi-enterrado
D	1805	241	Coberta Livre	Semi-enterrado
D	1806	238	Coberta Livre	Semi-enterrado
D	1806	239	Coberta Livre	Semi-enterrado
D	1807	236	Coberta Livre	Semi-enterrado
D	1807	237	Coberta Livre	Semi-enterrado
D	1808	235	Coberta Livre	Semi-enterrado
D	1808	260	Coberta Livre	Semi-enterrado
D	1809	258	Coberta Livre	Semi-enterrado
D	1809	259	Coberta Livre	Semi-enterrado
D	1810	256	Coberta Livre	Semi-enterrado
D	1810	257	Coberta Livre	Semi-enterrado
D	1811	254	Coberta Livre	Semi-enterrado
D	1811	255	Coberta Livre	Semi-enterrado
D	1812	252	Coberta Livre	Semi-enterrado
D	1812	253	Coberta Livre	Semi-enterrado
E	101	1153	Descoberta Livre	Mezzanino
E	102	1154	Descoberta Livre	Mezzanino
E	103	1155	Descoberta Livre	Mezzanino
E	104	1156	Descoberta Livre	Mezzanino
E	105	1157	Descoberta Livre	Mezzanino
E	106	1158	Descoberta Livre	Mezzanino
E	107	1159	Descoberta Livre	Mezzanino
E	108	1160	Descoberta Livre	Mezzanino
E	109	1161	Descoberta Livre	Mezzanino
E	110	1162	Descoberta Livre	Mezzanino
E	111	1163	Descoberta Livre	Mezzanino
E	112	1164	Descoberta Livre	Mezzanino
E	201	1141	Descoberta Livre	Mezzanino
E	202	1142	Descoberta Livre	Mezzanino
E	203	1143	Descoberta Livre	Mezzanino
E	204	1144	Descoberta Livre	Mezzanino
E	205	1145	Descoberta Livre	Mezzanino
E	206	1146	Descoberta Livre	Mezzanino
E	207	1147	Descoberta Livre	Mezzanino
E	208	1148	Descoberta Livre	Mezzanino
E	209	1149	Descoberta Livre	Mezzanino
E	210	1150	Descoberta Livre	Mezzanino
E	211	1151	Descoberta Livre	Mezzanino
E	212	1152	Descoberta Livre	Mezzanino
E	301	1223	Descoberta Livre	Mezzanino
E	302	1224	Descoberta Livre	Mezzanino
E	303	1225	Descoberta Livre	Mezzanino
E	304	1226	Descoberta Livre	Mezzanino
E	305	1227	Descoberta Livre	Mezzanino
E	306	1228	Descoberta Livre	Mezzanino
E	307	1229	Descoberta Livre	Mezzanino
E	308	1182	Descoberta Livre	Mezzanino
E	309	1181	Descoberta Livre	Mezzanino
E	310	1138	Descoberta Livre	Mezzanino
E	311	1139	Descoberta Livre	Mezzanino
E	312	1140	Descoberta Livre	Mezzanino
E	401	1211	Descoberta Livre	Mezzanino
E	402	1212	Descoberta Livre	Mezzanino
E	403	1213	Descoberta Livre	Mezzanino
E	404	1214	Descoberta Livre	Mezzanino
E	405	1215	Descoberta Livre	Mezzanino
E	406	1216	Descoberta Livre	Mezzanino
E	407	1217	Descoberta Livre	Mezzanino
E	408	1218	Descoberta Livre	Mezzanino
E	409	1219	Descoberta Livre	Mezzanino
E	410	1220	Descoberta Livre	Mezzanino
E	411	1221	Descoberta Livre	Mezzanino
E	412	1222	Descoberta Livre	Mezzanino
E	501	1199	Descoberta Livre	Mezzanino
E	502	1200	Descoberta Livre	Mezzanino
E	503	1201	Descoberta Livre	Mezzanino
E	504	1202	Descoberta Livre	Mezzanino
E	505	1203	Descoberta Livre	Mezzanino
E	506	1204	Descoberta Livre	Mezzanino
E	507	1205	Descoberta Livre	Mezzanino
E	508	1206	Descoberta Livre	Mezzanino
E	509	1207	Descoberta Livre	Mezzanino
E	510	1208	Descoberta Livre	Mezzanino
E	511	1209	Descoberta Livre	Mezzanino
E	512	1210	Descoberta Livre	Mezzanino
E	601	1187	Coberta Livre	Mezzanino
E	602	1188	Coberta Livre	Mezzanino
E	603	1189	Coberta Livre	Mezzanino
E	604	1190	Coberta Livre	Mezzanino
E	605	1191	Coberta Livre	Mezzanino
E	606	1192	Coberta Livre	Mezzanino
E	607	1193	Coberta Livre	Mezzanino
E	608	1194	Coberta Livre	Mezzanino
E	609	1195	Coberta Livre	Mezzanino
E	610	1196	Coberta Livre	Mezzanino
E	611	1197	Coberta Livre	Mezzanino
E	612	1198	Coberta Livre	Mezzanino
E	701	1173	Coberta Livre	Mezzanino
E	702	1174	Coberta Livre	Mezzanino
E	703	1175	Coberta Livre	Mezzanino
E	704	1176	Coberta Livre	Mezzanino
E	705	1177	Coberta Livre	Mezzanino
E	706	1178	Coberta Livre	Mezzanino
E	707	1179	Coberta Livre	Mezzanino
E	708	1180	Coberta Livre	Mezzanino
E	709	1183	Coberta Livre	Mezzanino
E	710	1184	Coberta Livre	Mezzanino
E	711	1185	Coberta Livre	Mezzanino
E	712	1186	Coberta Livre	Mezzanino
E	801	831	Coberta Livre	Térreo
E	802	830	Coberta Livre	Térreo
E	803	829	Coberta Livre	Térreo
E	804	828	Coberta Livre	Térreo
E	805	1165	Descoberta Livre	Mezzanino
E	806	1166	Coberta Livre	Mezzanino
E	807	1167	Coberta Livre	Mezzanino
E	808	1168	Coberta Livre	Mezzanino
E	809	1169	Coberta Livre	Mezzanino
E	810	1170	Coberta Livre	Mezzanino
E	811	1171	Coberta Livre	Mezzanino
E	812	1172	Coberta Livre	Mezzanino
E	901	843	Coberta Livre	Térreo
E	902	842	Coberta Livre	Térreo
E	903	841	Coberta Livre	Térreo
E	904	840	Coberta Livre	Térreo
E	905	839	Coberta Livre	Térreo
E	906	838	Coberta Livre	Térreo
E	907	837	Coberta Livre	Térreo
E	908	836	Coberta Livre	Térreo
E	909	835	Coberta Livre	Térreo
E	910	834	Coberta Livre	Térreo
E	911	833	Coberta Livre	Térreo
E	912	832	Coberta Livre	Térreo
E	1001	855	Coberta Livre	Térreo
E	1002	854	Coberta Livre	Térreo
E	1003	853	Coberta Livre	Térreo
E	1004	852	Coberta Livre	Térreo
E	1005	851	Coberta Livre	Térreo
E	1006	850	Coberta Livre	Térreo
E	1007	849	Coberta Livre	Térreo
E	1008	848	Coberta Livre	Térreo
E	1009	847	Coberta Livre	Térreo
E	1010	846	Coberta Livre	Térreo
E	1011	845	Coberta Livre	Térreo
E	1012	844	Coberta Livre	Térreo
E	1101	820	Coberta Livre	Térreo
E	1102	819	Coberta Livre	Térreo
E	1103	818	Coberta Livre	Térreo
E	1104	817	Coberta Livre	Térreo
E	1105	816	Coberta Livre	Térreo
E	1106	815	Coberta Livre	Térreo
E	1107	814	Coberta Livre	Térreo
E	1108	813	Coberta Livre	Térreo
E	1109	812	Coberta Livre	Térreo
E	1110	811	Coberta Livre	Térreo
E	1111	810	Coberta Livre	Térreo
E	1112	809	Coberta Livre	Térreo
E	1201	794	Coberta Livre	Térreo
E	1202	793	Coberta Livre	Térreo
E	1203	792	Coberta Livre	Térreo
E	1204	791	Coberta Livre	Térreo
E	1205	790	Coberta Livre	Térreo
E	1206	827	Coberta Livre	Térreo
E	1207	826	Coberta Livre	Térreo
E	1208	825	Coberta Livre	Térreo
E	1209	824	Coberta Livre	Térreo
E	1210	823	Coberta Livre	Térreo
E	1211	822	Coberta Livre	Térreo
E	1212	821	Coberta Livre	Térreo
E	1301	806	Coberta Livre	Térreo
E	1302	805	Coberta Livre	Térreo
E	1303	804	Coberta Livre	Térreo
E	1304	803	Coberta Livre	Térreo
E	1305	802	Coberta Livre	Térreo
E	1306	801	Coberta Livre	Térreo
E	1307	800	Coberta Livre	Térreo
E	1308	799	Coberta Livre	Térreo
E	1309	798	Coberta Livre	Térreo
E	1310	797	Coberta Livre	Térreo
E	1311	796	Coberta Livre	Térreo
E	1312	795	Coberta Livre	Térreo
E	1401	770	Coberta Livre	Térreo
E	1402	769	Coberta Livre	Térreo
E	1403	768	Coberta Livre	Térreo
E	1404	767	Coberta Livre	Térreo
E	1405	766	Coberta Livre	Térreo
E	1406	765	Coberta Livre	Térreo
E	1407	764	Coberta Livre	Térreo
E	1408	763	Coberta Livre	Térreo
E	1409	761	Coberta Livre	Térreo
E	1410	762	Coberta Livre	Térreo
E	1411	808	Coberta Livre	Térreo
E	1412	807	Coberta Livre	Térreo
E	1501	782	Coberta Livre	Térreo
E	1502	781	Coberta Livre	Térreo
E	1503	780	Coberta Livre	Térreo
E	1504	779	Coberta Livre	Térreo
E	1505	778	Coberta Livre	Térreo
E	1506	777	Coberta Livre	Térreo
E	1507	776	Coberta Livre	Térreo
E	1508	775	Coberta Livre	Térreo
E	1509	774	Coberta Livre	Térreo
E	1510	773	Coberta Livre	Térreo
E	1511	772	Coberta Livre	Térreo
E	1512	771	Coberta Livre	Térreo
E	1601	198	Coberta Livre	Semi-enterrado
E	1602	197	Coberta Livre	Semi-enterrado
E	1603	196	Coberta Livre	Semi-enterrado
E	1604	195	Coberta Livre	Semi-enterrado
E	1605	194	Coberta Livre	Semi-enterrado
E	1606	789	Coberta Livre	Térreo
E	1607	788	Coberta Livre	Térreo
E	1608	787	Coberta Livre	Térreo
E	1609	786	Coberta Livre	Térreo
E	1610	785	Coberta Livre	Térreo
E	1611	784	Coberta Livre	Térreo
E	1612	783	Coberta Livre	Térreo
E	1701	157	Coberta Livre	Semi-enterrado
E	1701	158	Coberta Livre	Semi-enterrado
E	1702	156	Coberta Livre	Semi-enterrado
E	1702	193	Coberta Livre	Semi-enterrado
E	1703	191	Coberta Livre	Semi-enterrado
E	1703	192	Coberta Livre	Semi-enterrado
E	1704	189	Coberta Livre	Semi-enterrado
E	1704	190	Coberta Livre	Semi-enterrado
E	1705	187	Coberta Livre	Semi-enterrado
E	1705	188	Coberta Livre	Semi-enterrado
E	1706	185	Coberta Livre	Semi-enterrado
E	1706	186	Coberta Livre	Semi-enterrado
E	1707	183	Coberta Livre	Semi-enterrado
E	1707	184	Coberta Livre	Semi-enterrado
E	1708	207	Coberta Livre	Semi-enterrado
E	1708	208	Coberta Livre	Semi-enterrado
E	1709	205	Coberta Livre	Semi-enterrado
E	1709	206	Coberta Livre	Semi-enterrado
E	1710	203	Coberta Livre	Semi-enterrado
E	1710	204	Coberta Livre	Semi-enterrado
E	1711	201	Coberta Livre	Semi-enterrado
E	1711	202	Coberta Livre	Semi-enterrado
E	1712	199	Coberta Livre	Semi-enterrado
E	1712	200	Coberta Livre	Semi-enterrado
E	1801	154	Coberta Livre	Semi-enterrado
E	1801	155	Coberta Livre	Semi-enterrado
E	1802	152	Coberta Livre	Semi-enterrado
E	1802	153	Coberta Livre	Semi-enterrado
E	1803	150	Coberta Livre	Semi-enterrado
E	1803	151	Coberta Livre	Semi-enterrado
E	1804	148	Coberta Livre	Semi-enterrado
E	1804	149	Coberta Livre	Semi-enterrado
E	1805	146	Coberta Livre	Semi-enterrado
E	1805	147	Coberta Livre	Semi-enterrado
E	1806	144	Coberta Livre	Semi-enterrado
E	1806	145	Coberta Livre	Semi-enterrado
E	1807	142	Coberta Livre	Semi-enterrado
E	1807	143	Coberta Livre	Semi-enterrado
E	1808	141	Coberta Livre	Semi-enterrado
E	1808	167	Coberta Livre	Semi-enterrado
E	1809	165	Coberta Livre	Semi-enterrado
E	1809	166	Coberta Livre	Semi-enterrado
E	1810	163	Coberta Livre	Semi-enterrado
E	1810	164	Coberta Livre	Semi-enterrado
E	1811	161	Coberta Livre	Semi-enterrado
E	1811	162	Coberta Livre	Semi-enterrado
E	1812	159	Coberta Livre	Semi-enterrado
E	1812	160	Coberta Livre	Semi-enterrado
F	101	1057	Descoberta Livre	Mezzanino
F	102	1056	Descoberta Livre	Mezzanino
F	103	1055	Descoberta Livre	Mezzanino
F	104	1054	Descoberta Livre	Mezzanino
F	105	1053	Descoberta Livre	Mezzanino
F	106	1052	Descoberta Livre	Mezzanino
F	107	1051	Descoberta Livre	Mezzanino
F	108	1050	Descoberta Livre	Mezzanino
F	109	1049	Descoberta Livre	Mezzanino
F	110	1048	Descoberta Livre	Mezzanino
F	111	1047	Descoberta Livre	Mezzanino
F	112	1046	Descoberta Livre	Mezzanino
F	201	1069	Descoberta Livre	Mezzanino
F	202	1068	Descoberta Livre	Mezzanino
F	203	1067	Descoberta Livre	Mezzanino
F	204	1066	Descoberta Livre	Mezzanino
F	205	1065	Descoberta Livre	Mezzanino
F	206	1064	Descoberta Livre	Mezzanino
F	207	1063	Descoberta Livre	Mezzanino
F	208	1062	Descoberta Livre	Mezzanino
F	209	1061	Descoberta Livre	Mezzanino
F	210	1060	Descoberta Livre	Mezzanino
F	211	1059	Descoberta Livre	Mezzanino
F	212	1058	Descoberta Livre	Mezzanino
F	301	1116	Descoberta Livre	Mezzanino
F	302	1115	Descoberta Livre	Mezzanino
F	303	1114	Descoberta Livre	Mezzanino
F	304	1113	Descoberta Livre	Mezzanino
F	305	1112	Descoberta Livre	Mezzanino
F	306	1111	Descoberta Livre	Mezzanino
F	307	1110	Descoberta Livre	Mezzanino
F	308	1109	Descoberta Livre	Mezzanino
F	309	1073	Descoberta Livre	Mezzanino
F	310	1072	Descoberta Livre	Mezzanino
F	311	1071	Descoberta Livre	Mezzanino
F	312	1070	Descoberta Livre	Mezzanino
F	401	1128	Descoberta Livre	Mezzanino
F	402	1127	Descoberta Livre	Mezzanino
F	403	1126	Descoberta Livre	Mezzanino
F	404	1125	Descoberta Livre	Mezzanino
F	405	1124	Descoberta Livre	Mezzanino
F	406	1123	Descoberta Livre	Mezzanino
F	407	1122	Descoberta Livre	Mezzanino
F	408	1121	Descoberta Livre	Mezzanino
F	409	1120	Descoberta Livre	Mezzanino
F	410	1119	Descoberta Livre	Mezzanino
F	411	1118	Descoberta Livre	Mezzanino
F	412	1117	Descoberta Livre	Mezzanino
F	501	1092	Coberta Livre	Mezzanino
F	502	1090	Descoberta Livre	Mezzanino
F	503	1091	Descoberta Livre	Mezzanino
F	504	1136	Descoberta Livre	Mezzanino
F	505	1137	Descoberta Livre	Mezzanino
F	506	1135	Descoberta Livre	Mezzanino
F	507	1134	Descoberta Livre	Mezzanino
F	508	1133	Descoberta Livre	Mezzanino
F	509	1132	Descoberta Livre	Mezzanino
F	510	1131	Descoberta Livre	Mezzanino
F	511	1130	Descoberta Livre	Mezzanino
F	512	1129	Descoberta Livre	Mezzanino
F	601	1104	Coberta Livre	Mezzanino
F	602	1103	Coberta Livre	Mezzanino
F	603	1102	Coberta Livre	Mezzanino
F	604	1101	Coberta Livre	Mezzanino
F	605	1100	Coberta Livre	Mezzanino
F	606	1099	Coberta Livre	Mezzanino
F	607	1098	Coberta Livre	Mezzanino
F	608	1097	Coberta Livre	Mezzanino
F	609	1096	Coberta Livre	Mezzanino
F	610	1095	Coberta Livre	Mezzanino
F	611	1094	Coberta Livre	Mezzanino
F	612	1093	Coberta Livre	Mezzanino
F	701	1082	Coberta Livre	Mezzanino
F	702	1083	Coberta Livre	Mezzanino
F	703	1084	Coberta Livre	Mezzanino
F	704	1085	Coberta Livre	Mezzanino
F	705	1086	Coberta Livre	Mezzanino
F	706	1087	Coberta Livre	Mezzanino
F	707	1088	Coberta Livre	Mezzanino
F	708	1089	Coberta Livre	Mezzanino
F	709	1108	Descoberta Livre	Mezzanino
F	710	1107	Coberta Livre	Mezzanino
F	711	1106	Coberta Livre	Mezzanino
F	712	1105	Coberta Livre	Mezzanino
F	801	757	Coberta Livre	Térreo
F	802	758	Coberta Livre	Térreo
F	803	759	Coberta Livre	Térreo
F	804	760	Coberta Livre	Térreo
F	805	1074	Descoberta Livre	Mezzanino
F	806	1075	Coberta Livre	Mezzanino
F	807	1076	Coberta Livre	Mezzanino
F	808	1077	Coberta Livre	Mezzanino
F	809	1078	Coberta Livre	Mezzanino
F	810	1079	Coberta Livre	Mezzanino
F	811	1080	Coberta Livre	Mezzanino
F	812	1081	Coberta Livre	Mezzanino
F	901	745	Coberta Livre	Térreo
F	902	746	Coberta Livre	Térreo
F	903	747	Coberta Livre	Térreo
F	904	748	Coberta Livre	Térreo
F	905	749	Coberta Livre	Térreo
F	906	750	Coberta Livre	Térreo
F	907	751	Coberta Livre	Térreo
F	908	752	Coberta Livre	Térreo
F	909	753	Coberta Livre	Térreo
F	910	754	Coberta Livre	Térreo
F	911	755	Coberta Livre	Térreo
F	912	756	Coberta Livre	Térreo
F	1001	715	Coberta Livre	Térreo
F	1002	734	Coberta Livre	Térreo
F	1003	735	Coberta Livre	Térreo
F	1004	736	Coberta Livre	Térreo
F	1005	737	Coberta Livre	Térreo
F	1006	738	Coberta Livre	Térreo
F	1007	739	Coberta Livre	Térreo
F	1008	740	Coberta Livre	Térreo
F	1009	741	Coberta Livre	Térreo
F	1010	742	Coberta Livre	Térreo
F	1011	743	Coberta Livre	Térreo
F	1012	744	Coberta Livre	Térreo
F	1101	727	Coberta Livre	Térreo
F	1102	726	Coberta Livre	Térreo
F	1103	725	Coberta Livre	Térreo
F	1104	724	Coberta Livre	Térreo
F	1105	723	Coberta Livre	Térreo
F	1106	722	Coberta Livre	Térreo
F	1107	721	Coberta Livre	Térreo
F	1108	720	Coberta Livre	Térreo
F	1109	719	Coberta Livre	Térreo
F	1110	718	Coberta Livre	Térreo
F	1111	717	Coberta Livre	Térreo
F	1112	716	Coberta Livre	Térreo
F	1201	709	Coberta Livre	Térreo
F	1202	710	Coberta Livre	Térreo
F	1203	711	Coberta Livre	Térreo
F	1204	712	Coberta Livre	Térreo
F	1205	713	Coberta Livre	Térreo
F	1206	714	Coberta Livre	Térreo
F	1207	733	Coberta Livre	Térreo
F	1208	732	Coberta Livre	Térreo
F	1209	731	Coberta Livre	Térreo
F	1210	730	Coberta Livre	Térreo
F	1211	729	Coberta Livre	Térreo
F	1212	728	Coberta Livre	Térreo
F	1301	697	Coberta Livre	Térreo
F	1302	698	Coberta Livre	Térreo
F	1303	699	Coberta Livre	Térreo
F	1304	700	Coberta Livre	Térreo
F	1305	701	Coberta Livre	Térreo
F	1306	702	Coberta Livre	Térreo
F	1307	703	Coberta Livre	Térreo
F	1308	704	Coberta Livre	Térreo
F	1309	705	Coberta Livre	Térreo
F	1310	706	Coberta Livre	Térreo
F	1311	707	Coberta Livre	Térreo
F	1312	708	Coberta Livre	Térreo
F	1401	676	Coberta Livre	Térreo
F	1402	675	Coberta Livre	Térreo
F	1403	674	Coberta Livre	Térreo
F	1404	673	Coberta Livre	Térreo
F	1405	672	Coberta Livre	Térreo
F	1406	671	Coberta Livre	Térreo
F	1407	670	Coberta Livre	Térreo
F	1408	669	Coberta Livre	Térreo
F	1409	668	Coberta Livre	Térreo
F	1410	667	Coberta Livre	Térreo
F	1411	666	Coberta Livre	Térreo
F	1412	696	Coberta Livre	Térreo
F	1501	688	Coberta Livre	Térreo
F	1502	687	Coberta Livre	Térreo
F	1503	686	Coberta Livre	Térreo
F	1504	685	Coberta Livre	Térreo
F	1505	684	Coberta Livre	Térreo
F	1506	683	Coberta Livre	Térreo
F	1507	682	Coberta Livre	Térreo
F	1508	681	Coberta Livre	Térreo
F	1509	680	Coberta Livre	Térreo
F	1510	679	Coberta Livre	Térreo
F	1511	678	Coberta Livre	Térreo
F	1512	677	Coberta Livre	Térreo
F	1601	110	Coberta Livre	Semi-enterrado
F	1602	111	Coberta Livre	Semi-enterrado
F	1603	112	Coberta Livre	Semi-enterrado
F	1604	113	Coberta Livre	Semi-enterrado
F	1605	114	Coberta Livre	Semi-enterrado
F	1606	695	Coberta Livre	Térreo
F	1607	694	Coberta Livre	Térreo
F	1608	693	Coberta Livre	Térreo
F	1609	692	Coberta Livre	Térreo
F	1610	691	Coberta Livre	Térreo
F	1611	690	Coberta Livre	Térreo
F	1612	689	Coberta Livre	Térreo
F	1701	72	Coberta Livre	Semi-enterrado
F	1701	99	Coberta Livre	Semi-enterrado
F	1702	97	Coberta Livre	Semi-enterrado
F	1702	98	Coberta Livre	Semi-enterrado
F	1703	96	Coberta Livre	Semi-enterrado
F	1704	95	Coberta Livre	Semi-enterrado
F	1705	94	Coberta Livre	Semi-enterrado
F	1706	92	Coberta Livre	Semi-enterrado
F	1706	93	Coberta Livre	Semi-enterrado
F	1707	90	Coberta Livre	Semi-enterrado
F	1707	91	Coberta Livre	Semi-enterrado
F	1708	100	Coberta Livre	Semi-enterrado
F	1708	101	Coberta Livre	Semi-enterrado
F	1709	102	Coberta Livre	Semi-enterrado
F	1709	103	Coberta Livre	Semi-enterrado
F	1710	104	Coberta Livre	Semi-enterrado
F	1710	105	Coberta Livre	Semi-enterrado
F	1711	106	Coberta Livre	Semi-enterrado
F	1711	107	Coberta Livre	Semi-enterrado
F	1712	108	Coberta Livre	Semi-enterrado
F	1712	109	Coberta Livre	Semi-enterrado
F	1801	60	Coberta Livre	Semi-enterrado
F	1801	61	Coberta Livre	Semi-enterrado
F	1802	58	Coberta Livre	Semi-enterrado
F	1802	59	Coberta Livre	Semi-enterrado
F	1803	56	Coberta Livre	Semi-enterrado
F	1803	57	Coberta Livre	Semi-enterrado
F	1804	54	Coberta Livre	Semi-enterrado
F	1804	55	Coberta Livre	Semi-enterrado
F	1805	52	Coberta Livre	Semi-enterrado
F	1805	53	Coberta Livre	Semi-enterrado
F	1806	50	Coberta Livre	Semi-enterrado
F	1806	51	Coberta Livre	Semi-enterrado
F	1807	48	Coberta Livre	Semi-enterrado
F	1807	49	Coberta Livre	Semi-enterrado
F	1808	62	Coberta Livre	Semi-enterrado
F	1808	63	Coberta Livre	Semi-enterrado
F	1809	64	Coberta Livre	Semi-enterrado
F	1809	65	Coberta Livre	Semi-enterrado
F	1810	66	Coberta Livre	Semi-enterrado
F	1810	67	Coberta Livre	Semi-enterrado
F	1811	68	Coberta Livre	Semi-enterrado
F	1811	69	Coberta Livre	Semi-enterrado
F	1812	70	Coberta Livre	Semi-enterrado
F	1812	71	Coberta Livre	Semi-enterrado';


// dump($vagas);

// exit;


$tabela  =  "<table>";
$tabela .=   "<tr>";
$tabela .=    "<th>Bloco</th><th>Unidade</th><th>Vaga</th><th>Tipo</th><th>Local</th>";
$tabela .=    "</tr>";

foreach($vagas as $k => $v){
	// dump($v);
	$it = explode("\n",$v);

	for ($i = 0; $i < 12; $i++){
		$plumada = sprintf('%02d', $i + 1); // Formata o valor para ter sempre 2 dígitos
		$unidades = explode("\t", $it[$i]);
		
		$andar = 0;
		foreach($unidades as $un){
			$un = explode("/",$un);
			// dump(sizeof($un));
			// continue;
			$andar++;
			if(sizeof($un) == 1){
				$un=intval($un[0]);
				$tabela .=    "<tr>";
					$tabela .=    "<td>$k</td><td>$andar$plumada</td><td>$un</td><td></td><td></td>";
				$tabela .=    "</tr>";
				$porVaga[$un]['bloco'] = $k;
				$porVaga[$un]['unidade'] = $andar.$plumada;
				
			}else{
				foreach($un as $nu){
					$nu = intval($nu);
				$tabela .=    "<tr>";
					$tabela .=    "<td>$k</td><td>$andar$plumada</td><td>$nu</td><td></td><td></td>";
				$tabela .=    "</tr>";
				
				$porVaga[$nu]['bloco'] = $k;
				$porVaga[$nu]['unidade'] = $andar.$plumada;
				}
			}
		}

		
	}
}


$tabela .=    "</table>";
echo $tabela;
// sort($porVaga);
// dump($porVaga);
// dump($vagasCompleto);
$vgs = explode("\n", $vagasCompleto);

foreach($vgs as $vg){
	$vg = explode("\t",$vg);
	$dados['bloco'] = $vg[0];
	$dados['unidade'] = $vg[1];
	$dados['id_estacionamento'] = $vg[2];
	$dados['tipo'] = $vg[3];
	$dados['local'] = str_replace("\r", "", $vg[4]);
	
	// upsertEstacionamento($dados);
	
	// dump($vg);
	// dump($dados);
}

?>