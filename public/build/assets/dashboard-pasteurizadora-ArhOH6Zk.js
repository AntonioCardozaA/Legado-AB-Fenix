const b=JSON.parse(document.getElementById("pasteurDashboardData").textContent);b.pasteurizadorasData;const w=b.fallasPorLineaPasteurizadora,z=b.componentesDanadosPasteurizadora,V=b.planesAccionDashboardPasteurizadora,k=b.avanceRevisionPasteurizadora,E=b.analisis52124Pasteurizadora,B=b.analisis30147Pasteurizadora;b.centralesHidraulicasData;const x=b.fallasPorLineaCentralHidraulica,T=b.planesAccionDashboardCentralHidraulica,A=b.avanceRevisionCentralHidraulica,_=b.analisis52124CentralHidraulica,R=b.analisis30147CentralHidraulica,H={52124:"bar",30147:"bar",central52124:"bar",central30147:"bar"},v={52124:{key:"52124",canvasId:"analisis52124PasteurizadoraChart",selectId:"analisis52124PasteurizadoraLineaSelect",dataset:E,statusCardId:"trend52124StatusCard",statusTitleId:"trend52124StatusTitle",statusCopyId:"trend52124StatusCopy",statusNoteId:"trend52124StatusNote",windowsId:"trend52124WindowCards",machineGridId:"trend52124MachineGrid",captionId:"trend52124Caption",title:"52-12-4",windowRoles:["Histórico anual","Impacto trimestral","Control inmediato"],colors:[["rgba(16, 185, 129, 0.88)","#047857","rgba(16, 185, 129, 0.22)"],["rgba(239, 68, 68, 0.88)","#dc2626","rgba(239, 68, 68, 0.22)"],["rgba(245, 158, 11, 0.9)","#d97706","rgba(245, 158, 11, 0.24)"]]},30147:{key:"30147",canvasId:"analisis30147PasteurizadoraChart",selectId:"analisis30147PasteurizadoraLineaSelect",dataset:B,statusCardId:"trend30147StatusCard",statusTitleId:"trend30147StatusTitle",statusCopyId:"trend30147StatusCopy",statusNoteId:"trend30147StatusNote",windowsId:"trend30147WindowCards",machineGridId:"trend30147MachineGrid",captionId:"trend30147Caption",title:"30-14-7",windowRoles:["Ventana amplia","Seguimiento intermedio","Control inmediato"],colors:[["rgba(16, 185, 129, 0.88)","#047857","rgba(16, 185, 129, 0.22)"],["rgba(239, 68, 68, 0.88)","#dc2626","rgba(239, 68, 68, 0.22)"],["rgba(245, 158, 11, 0.9)","#d97706","rgba(245, 158, 11, 0.24)"]]},central52124:{key:"central52124",canvasId:"analisis52124CentralHidraulicaChart",selectId:"analisis52124CentralHidraulicaLineaSelect",dataset:_,statusCardId:"trendCentral52124StatusCard",statusTitleId:"trendCentral52124StatusTitle",statusCopyId:"trendCentral52124StatusCopy",statusNoteId:"trendCentral52124StatusNote",windowsId:"trendCentral52124WindowCards",machineGridId:"trendCentral52124MachineGrid",captionId:"trendCentral52124Caption",title:"Central Hidraulica 52-12-4",windowRoles:["Historico anual","Impacto trimestral","Control inmediato"],colors:[["rgba(20, 184, 166, 0.88)","#0f766e","rgba(20, 184, 166, 0.22)"],["rgba(239, 68, 68, 0.88)","#dc2626","rgba(239, 68, 68, 0.22)"],["rgba(245, 158, 11, 0.9)","#d97706","rgba(245, 158, 11, 0.24)"]]},central30147:{key:"central30147",canvasId:"analisis30147CentralHidraulicaChart",selectId:"analisis30147CentralHidraulicaLineaSelect",dataset:R,statusCardId:"trendCentral30147StatusCard",statusTitleId:"trendCentral30147StatusTitle",statusCopyId:"trendCentral30147StatusCopy",statusNoteId:"trendCentral30147StatusNote",windowsId:"trendCentral30147WindowCards",machineGridId:"trendCentral30147MachineGrid",captionId:"trendCentral30147Caption",title:"Central Hidraulica 30-14-7",windowRoles:["Ventana amplia","Seguimiento intermedio","Control inmediato"],colors:[["rgba(20, 184, 166, 0.88)","#0f766e","rgba(20, 184, 166, 0.22)"],["rgba(239, 68, 68, 0.88)","#dc2626","rgba(239, 68, 68, 0.22)"],["rgba(245, 158, 11, 0.9)","#d97706","rgba(245, 158, 11, 0.24)"]]}};document.addEventListener("DOMContentLoaded",function(){F(),K(),oe(),re()});function F(){const e=document.getElementById(b.fallasCanvasId);if(e){const t=e.getContext("2d");new Chart(t,{type:"bar",data:{labels:w.map(a=>a.linea),datasets:[{label:"Críticos",data:w.map(a=>a.criticos||0),backgroundColor:"rgba(239, 68, 68, 0.9)",borderColor:"#dc2626",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Requiere revisión",data:w.map(a=>a.requiere_revision||0),backgroundColor:"rgba(245, 158, 11, 0.9)",borderColor:"#d97706",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Severo / Moderado",data:w.map(a=>a.desgaste||0),backgroundColor:"rgba(249, 115, 22, 0.88)",borderColor:"#ea580c",borderWidth:2,borderRadius:12,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,stacked:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",padding:8}},x:{stacked:!0,grid:{display:!1,drawBorder:!1},ticks:{font:{size:13,weight:600},color:"#334155",padding:8}}},plugins:{legend:{display:!0,position:"top",labels:{usePointStyle:!0,padding:18,font:{size:12,weight:"bold"},color:"#334155"}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#3b82f6",borderWidth:2,padding:14,callbacks:{label:function(a){return`${a.dataset.label}: ${a.raw}`},footer:function(a){const r=w[a[0]?.dataIndex];return r?`Total: ${r.total_fallas||0}`:""}}}}}})}G(),O(),Z(),C("analisis52124PasteurizadoraChart",E,v[52124]),C("analisis30147PasteurizadoraChart",B,v[30147]),U(),X(),J(),C("analisis52124CentralHidraulicaChart",_,v.central52124),C("analisis30147CentralHidraulicaChart",R,v.central30147)}function G(){const e=document.getElementById("componentesPasteurizadoraChart");if(!e)return null;const t=Array.isArray(z)?z.filter(a=>Number(a.total_danios||0)>0):[];return new Chart(e.getContext("2d"),{type:"doughnut",data:{labels:t.map(a=>a.componente||"Sin componente"),datasets:[{data:t.map(a=>Number(a.total_danios||0)),backgroundColor:["rgba(239, 68, 68, 0.9)","rgba(245, 158, 11, 0.9)","rgba(16, 185, 129, 0.9)","rgba(59, 130, 246, 0.9)","rgba(139, 92, 246, 0.9)","rgba(236, 72, 153, 0.9)"],borderColor:["#dc2626","#d97706","#059669","#2563eb","#7c3aed","#db2777"],borderWidth:3,borderRadius:8,hoverBorderWidth:5,hoverOffset:12,spacing:3}]},options:{responsive:!0,maintainAspectRatio:!1,cutout:"62%",plugins:{legend:{position:"right",labels:{color:"#334155",padding:16,usePointStyle:!0,pointStyle:"circle",boxWidth:10,font:{size:12,weight:600,family:"'Inter', sans-serif"}}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#f59e0b",borderWidth:2,padding:14,callbacks:{label:a=>{const r=a.dataset.data.reduce((p,i)=>p+Number(i||0),0),o=Number(a.raw||0),g=r>0?(o/r*100).toFixed(1):"0.0";return[`Daños: ${o}`,`${g}%`]}}}}}})}function O(){const e=document.getElementById("planesPasteurizadoraChart");if(!e)return null;const t=V?.resumen||{};return new Chart(e.getContext("2d"),{type:"doughnut",data:{labels:["Pendientes críticos","Activos programados","Completados"],datasets:[{data:[Number(t.pendientes||0),Number(t.programados||0),Number(t.completados||0)],backgroundColor:["rgba(239, 68, 68, 0.92)","rgba(245, 158, 11, 0.88)","rgba(16, 185, 129, 0.88)"],borderColor:["#dc2626","#d97706","#059669"],borderWidth:2,hoverOffset:10}]},options:{responsive:!0,maintainAspectRatio:!1,cutout:"68%",plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#3b82f6",borderWidth:2,padding:14,callbacks:{label:a=>`${a.label}: ${a.raw}`}}}},plugins:[{id:"pasteurPlanesCenterLabel",beforeDraw(a){const{ctx:r}=a;r.save(),r.textAlign="center",r.textBaseline="middle",r.fillStyle="#0f172a",r.font="700 24px sans-serif",r.fillText(`${Number(t.avance||0)}%`,a.width/2,a.height/2-6),r.fillStyle="#64748b",r.font="600 11px sans-serif",r.fillText("avance global",a.width/2,a.height/2+16),r.restore()}}]})}function Z(){const e=document.getElementById("avanceRevisionPasteurizadoraChart");if(!e)return null;const t=Array.isArray(k?.labels)?k.labels:[],a=Array.isArray(k?.porcentajes)?k.porcentajes.map(r=>Number(r||0)):[];return new Chart(e.getContext("2d"),{type:"bar",data:{labels:t,datasets:[{label:"Avance de revisión mecánica",data:a,backgroundColor:a.map(r=>r>=90?"rgba(16, 185, 129, 0.88)":r>=60?"rgba(245, 158, 11, 0.88)":"rgba(249, 115, 22, 0.88)"),borderColor:a.map(r=>r>=90?"#059669":r>=60?"#d97706":"#ea580c"),borderWidth:2,borderRadius:10,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,max:100,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",callback:r=>`${r}%`}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155"}}},plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#10b981",borderWidth:2,padding:14,callbacks:{label:r=>`${r.raw}% revisado`}}}}})}function U(){const e=document.getElementById("fallasCentralHidraulicaChart");return e?new Chart(e.getContext("2d"),{type:"bar",data:{labels:x.map(t=>t.linea),datasets:[{label:"Criticos",data:x.map(t=>t.criticos||0),backgroundColor:"rgba(239, 68, 68, 0.9)",borderColor:"#dc2626",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Requiere revision",data:x.map(t=>t.requiere_revision||0),backgroundColor:"rgba(245, 158, 11, 0.9)",borderColor:"#d97706",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Severo / Moderado",data:x.map(t=>t.desgaste||0),backgroundColor:"rgba(20, 184, 166, 0.86)",borderColor:"#0f766e",borderWidth:2,borderRadius:12,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,stacked:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",padding:8}},x:{stacked:!0,grid:{display:!1,drawBorder:!1},ticks:{font:{size:13,weight:600},color:"#334155",padding:8}}},plugins:{legend:{display:!0,position:"top",labels:{usePointStyle:!0,padding:18,font:{size:12,weight:"bold"},color:"#334155"}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#ccfbf1",borderColor:"#0f766e",borderWidth:2,padding:14,callbacks:{label:t=>`${t.dataset.label}: ${t.raw}`,footer:function(t){const a=x[t[0]?.dataIndex];return a?`Total: ${a.total_fallas||0}`:""}}}}}}):null}function X(){const e=document.getElementById("planesCentralHidraulicaChart");if(!e)return null;const t=Array.isArray(T?.por_linea)?T.por_linea:[];return new Chart(e.getContext("2d"),{type:"bar",data:{labels:t.map(a=>a.linea||"N/A"),datasets:[{label:"Abiertos",data:t.map(a=>Number(a.abiertos||0)),backgroundColor:"rgba(239, 68, 68, 0.86)",borderColor:"#dc2626",borderWidth:2,borderRadius:10,borderSkipped:!1},{label:"Completados",data:t.map(a=>Number(a.completados||0)),backgroundColor:"rgba(20, 184, 166, 0.86)",borderColor:"#0f766e",borderWidth:2,borderRadius:10,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",precision:0}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155"}}},plugins:{legend:{position:"top",labels:{usePointStyle:!0,padding:18,font:{size:12,weight:"bold"},color:"#334155"}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#ccfbf1",borderColor:"#0f766e",borderWidth:2,padding:14}}}})}function J(){const e=document.getElementById("avanceRevisionCentralHidraulicaChart");if(!e)return null;const t=Array.isArray(A?.labels)?A.labels:[],a=Array.isArray(A?.porcentajes)?A.porcentajes.map(r=>Number(r||0)):[];return new Chart(e.getContext("2d"),{type:"bar",data:{labels:t,datasets:[{label:"Avance de revision hidraulica",data:a,backgroundColor:a.map(r=>r>=90?"rgba(20, 184, 166, 0.88)":r>=60?"rgba(245, 158, 11, 0.88)":"rgba(249, 115, 22, 0.88)"),borderColor:a.map(r=>r>=90?"#0f766e":r>=60?"#d97706":"#ea580c"),borderWidth:2,borderRadius:10,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,max:100,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",callback:r=>`${r}%`}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155"}}},plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#ccfbf1",borderColor:"#0f766e",borderWidth:2,padding:14,callbacks:{label:r=>`${r.raw}% revisado`}}}}})}function K(){document.querySelectorAll("[data-pasteur-trend-type]").forEach(e=>{e.addEventListener("click",()=>{const t=e.dataset.pasteurTrendCard,a=e.dataset.pasteurTrendType||"bar",r=v[t];r&&(H[t]=a,document.querySelectorAll(`[data-pasteur-trend-type][data-pasteur-trend-card="${t}"]`).forEach(o=>{o.classList.toggle("active",o.dataset.pasteurTrendType===a)}),C(r.canvasId,r.dataset,r))})}),Object.values(v).forEach(e=>{const t=document.getElementById(e.selectId);t&&t.addEventListener("change",()=>{C(e.canvasId,e.dataset,e),e.key==="52124"||e.key==="30147"||e.key==="central52124"||e.key})})}function $(e){return new Intl.NumberFormat("es-MX",{maximumFractionDigits:0}).format(Number(e||0))}function L(e,t){return(Array.isArray(e?.series)?e.series:[]).find(a=>a.key===t)||null}function S(e,t){const a=L(e,t),r=Array.isArray(a?.data)?a.data:[];return Number(r[r.length-1]||0)}function Q(e,t){const a=L(e,t),r=Array.isArray(a?.data)?a.data:[];return Number(r.length>1?r[r.length-2]:0)}function Y(e,t){return t.reduce((a,r)=>a+S(e,r.key),0)}function ee(e){return e<0?`Bajó ${$(Math.abs(e))} vs corte anterior`:e>0?`Subió ${$(e)} vs corte anterior`:"Sin cambio vs corte anterior"}function ae(e,t,a){return t.map((r,o)=>{const g=e.reduce((d,u)=>d+S(u,r.key),0),p=e.reduce((d,u)=>d+Q(u,r.key),0),i=g-p,l=e.filter(d=>S(d,r.key)>0).length;return{key:r.key,label:String(r.label||`Ventana ${o+1}`),current:g,previous:p,diff:i,impacted:l,role:a.windowRoles?.[o]||"Ventana de seguimiento",color:a.colors?.[o%(a.colors?.length||1)]?.[1]||"#3b82f6"}})}function te(e,t,a){const r=document.getElementById(a.statusCardId),o=document.getElementById(a.statusTitleId),g=document.getElementById(a.statusCopyId),p=document.getElementById(a.statusNoteId),i=document.getElementById(a.windowsId),l=document.getElementById(a.machineGridId),d=document.getElementById(a.captionId),u=ae(e,t,a),s=u[u.length-1]||null,f=u[1]||null,m=e.find(c=>Array.isArray(c?.labels)&&c.labels.length)?.labels?.slice(-1)?.[0]||a.dataset?.periodo?.label||"periodo actual";if(l&&(l.innerHTML=e.length?e.map(c=>{const h=Y(c,t),y=h>0?"active":"inactive",I=h>0?"fa-chart-line":"fa-circle-check";return`
                        <span class="dashboard-trend-machine-pill ${y}">
                            <i class="fas ${I}"></i>
                            ${n(c.linea||"N/A")}
                        </span>
                    `}).join(""):`
                    <span class="dashboard-trend-machine-pill inactive">
                        <i class="fas fa-circle-info"></i>
                        Sin pasteurizadoras
                    </span>
                `),i&&(u.length?i.innerHTML=u.map(c=>{const h=c.diff<0?"positive":c.diff>0?"alert":"neutral",y=c.diff<0?"fa-arrow-down":c.diff>0?"fa-arrow-up":"fa-minus";return`
                        <article class="dashboard-trend-window-card" style="--window-accent: ${c.color}">
                            <div class="dashboard-trend-window-label">${n(c.label)}</div>
                            <div class="dashboard-trend-window-value">${$(c.current)}</div>
                            <div class="dashboard-trend-window-role">${n(c.role)}</div>
                            <div class="dashboard-trend-window-delta ${h}">
                                <i class="fas ${y}"></i>
                                <span>${ee(c.diff)}</span>
                            </div>
                        </article>
                    `}).join(""):i.innerHTML=`
                    <article class="dashboard-trend-window-card" style="--window-accent: #64748b">
                        <div class="dashboard-trend-window-label">Sin datos</div>
                        <div class="dashboard-trend-window-value">0</div>
                        <div class="dashboard-trend-window-role">No hay información de ventanas para el periodo filtrado.</div>
                    </article>
                `),r&&o&&g){let c="neutral",h="Sin danos recientes",y="La ventana reciente no registra danos para esta seleccion.";!e.length||!t.length?(h="Sin datos en el periodo",y="No hay daños registrados para construir esta tendencia con el filtro actual."):(s?.current||0)===0?c="positive":(s?.diff||0)>0||(f?.diff||0)>0?(c="alert",h="Repunte reciente",y=`${s?.label||"La ventana reciente"} o ${f?.label||"la ventana intermedia"} subieron frente al corte anterior. Revisa las pasteurizadoras con mayor incidencia.`):(s?.diff||0)<=0&&(f?.diff||0)<=0&&(c="positive",h="Tendencia bajo control",y=`${s?.label||"La ventana reciente"} no aumenta frente al corte anterior y el comportamiento general se mantiene estable.`),r.className=`dashboard-trend-status dashboard-trend-status--${c}`,o.textContent=h,g.textContent=y}if(p){const c=s?`${$(s.impacted)} de ${$(e.length)} pasteurizadoras con daños en ${s.label}.`:"Sin pasteurizadoras con daños recientes para esta ventana.";p.textContent=c}d&&(d.textContent=`Corte actual: ${m}. Comparativo ${a.title} construido solo con análisis de Pasteurizadora.`)}function C(e,t,a={}){const r=document.getElementById(e);if(!r)return null;const o=Chart.getChart(r);o&&o.destroy();const g=Array.isArray(t?.lineas)?t.lineas:[],p=document.getElementById(a.selectId),i=p?.value||t?.default_linea_id||g[0]?.linea_id||"",l=g.find(h=>Number(h.linea_id)===Number(i))||g[0]||null,d=Array.isArray(l?.labels)?l.labels:[],u=Array.isArray(l?.series)?l.series:[];p&&l?.linea_id&&(p.value=String(l.linea_id));const s=a.colors||[["rgba(16, 185, 129, 0.88)","#047857","rgba(16, 185, 129, 0.22)"],["rgba(239, 68, 68, 0.88)","#dc2626","rgba(239, 68, 68, 0.22)"],["rgba(245, 158, 11, 0.9)","#d97706","rgba(245, 158, 11, 0.24)"]],f=H[a.key]||a.chartType||"bar",m=f==="bar",c=u.map((h,y)=>({label:String(h.label||`Serie ${y+1}`).replace(/dias/gi,"días"),data:Array.isArray(h.data)?h.data.map(I=>Number(I||0)):[],backgroundColor:m?s[y%s.length][0]:s[y%s.length][2]||s[y%s.length][0],borderColor:s[y%s.length][1],borderWidth:m?0:y===u.length-1?4:3,borderDash:m?[]:y===0?[8,6]:[],borderRadius:m?10:0,borderSkipped:m?!1:void 0,tension:m?0:.32,pointRadius:m?0:4,pointHoverRadius:m?0:7,pointBackgroundColor:s[y%s.length][1],pointBorderColor:"#ffffff",pointBorderWidth:m?0:2,fill:!1,maxBarThickness:m?28:void 0,categoryPercentage:m?.72:void 0,barPercentage:m?.74:void 0}));return te(l?[l]:[],u,a),new Chart(r.getContext("2d"),{type:f,data:{labels:d,datasets:c},options:{responsive:!0,maintainAspectRatio:!1,interaction:{mode:"index",intersect:!1},scales:{y:{beginAtZero:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",padding:8,precision:0}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155",padding:8}}},plugins:{tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#10b981",borderWidth:2,padding:14,callbacks:{label:h=>`${h.dataset.label}: ${h.raw} daños`}},legend:{position:"bottom",labels:{usePointStyle:!0,pointStyle:"circle",boxWidth:10,padding:14,font:{size:12,weight:"bold"},color:"#334155"}}}}})}function M(){window.location.reload()}function re(){setInterval(()=>{M()},3e5)}function oe(){document.querySelectorAll(".lavadora-carousel").forEach(e=>{const t=e.querySelectorAll(".carousel-slide"),a=e.querySelector(".carousel-prev"),r=e.querySelector(".carousel-next"),o=e.querySelectorAll(".carousel-dot");let g=0;function p(d){t.forEach((u,s)=>{u.classList.toggle("active",s===d)}),o.forEach((u,s)=>{u.classList.toggle("active",s===d)}),g=d}function i(){t.length<=1||p((g+1)%t.length)}function l(){t.length<=1||p((g-1+t.length)%t.length)}r&&r.addEventListener("click",i),a&&a.addEventListener("click",l),o.forEach(d=>{d.addEventListener("click",()=>{const u=parseInt(d.dataset.index,10);isNaN(u)||p(u)})}),t.length>1&&setInterval(i,6e3)})}function n(e){return String(e??"").replace(/[&<>"']/g,function(t){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[t]})}function se(e){if(!e)return"Sin fecha";const t=new Date(e);return Number.isNaN(t.getTime())?n(e):t.toLocaleDateString("es-MX")}function P(e){const t=e.analisis_por_estado||{};return{critico:Array.isArray(t.critico)?t.critico:Array.isArray(e.analisis_criticos)?e.analisis_criticos:[],severo:Array.isArray(t.severo)?t.severo:[],moderado:Array.isArray(t.moderado)?t.moderado:[],revision:Array.isArray(t.revision)?t.revision:Array.isArray(e.analisis_revision)?e.analisis_revision:[]}}function W(e){const t=P(e);return[{key:"critico",title:"Requiere cambio",tone:"critico"},{key:"severo",title:"Daño severo",tone:"severo"},{key:"moderado",title:"Daño moderado",tone:"moderado"},{key:"revision",title:"Requiere revision",tone:"revision"}].filter(a=>Array.isArray(t[a.key])&&t[a.key].length>0)}function ne(e,t){return t==="central"?[e.piso?`Piso: ${e.piso}`:null,e.lado||null].filter(Boolean).join(" · "):[e.modulo?`Modulo: ${e.modulo}`:null,e.nivel?`Nivel: ${e.nivel}`:null,e.lado||null].filter(Boolean).join(" · ")}function D(e,t,a,r="mecanica"){if(!Array.isArray(a)||a.length===0)return"";const g={critico:"bg-red-100 text-red-700",severo:"bg-orange-100 text-orange-700",moderado:"bg-amber-100 text-amber-700",revision:"bg-yellow-100 text-yellow-700"}[t]||"bg-slate-100 text-slate-700",p=a.map(i=>{const l="/images/icono_pas.png",d=i.image||i.fallback_image||l,u=ne(i,r),s=i.componente_nombre||i.componente?.nombre||"Componente",f=i.componentes_revisados_label||"",m=i.fecha_formateada||se(i.fecha_analisis),c=i.numero_orden?`Orden: ${i.numero_orden}`:null;return`
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="componente-header">
                                <div class="componente-icono">
                                    <img src="${n(d)}" class="w-8 h-8 object-contain" onerror="this.src='${n(i.fallback_image||l)}'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="componente-nombre">${n(s)}</div>
                                    ${f?`<p class="text-sm font-semibold text-gray-700 mt-1">${n(f)}</p>`:""}
                                </div>
                            </div>
                            ${u?`<p class="text-sm text-gray-600 mt-2">${n(u)}</p>`:""}
                            <p class="text-xs text-gray-500 mt-1">
                                Fecha: ${n(m)}${c?` · ${n(c)}`:""}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${g}">
                            ${n(i.estado_label||i.estado||e)}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2">
                        ${n(i.actividad||"Sin descripcion")}
                    </p>
                </div>
            `}).join("");return`
            <div class="mb-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h4 class="font-bold text-gray-800">${n(e)}</h4>
                    <span class="severity-pill ${t}">${a.length}</span>
                </div>
                <div class="space-y-3">
                    ${p}
                </div>
            </div>
        `}function j(e){return Object.values(e||{}).reduce((a,r)=>a+Number(r||0),0)<=0?"":`
            <div class="mb-4 flex flex-wrap gap-2">
                ${Number(e.critico||0)>0?`<span class="severity-pill critico">${Number(e.critico||0)} requiere cambio</span>`:""}
                ${Number(e.severo||0)>0?`<span class="severity-pill severo">${Number(e.severo||0)} severo</span>`:""}
                ${Number(e.moderado||0)>0?`<span class="severity-pill moderado">${Number(e.moderado||0)} moderado</span>`:""}
                ${Number(e.revision||0)>0?`<span class="severity-pill revision">${Number(e.revision||0)} revision</span>`:""}
            </div>
        `}function q(e){return`
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm text-slate-600">
                ${n(e)}
            </div>
        `}function ie(e){const t=document.getElementById("alertModal"),a=document.getElementById("modalTitle"),r=document.getElementById("modalBody"),o=e.estado||{},g=P(o),p=W(o),i=o.conteo_alertas||{};a.innerHTML=`Detalle - ${n(e.nombre)}`;let l=`
            <div class="mb-4 p-4 rounded-lg ${o.nivel==="critico"?"bg-red-50 border-l-4 border-red-500":o.nivel==="riesgo"?"bg-orange-50 border-l-4 border-orange-500":o.nivel==="operativo"?"bg-yellow-50 border-l-4 border-yellow-500":"bg-green-50 border-l-4 border-green-500"}">
                <h4 class="font-bold text-lg mb-2">Estado: ${n((o.nivel||"bueno").toUpperCase())}</h4>
                <p class="text-gray-700">${n(o.mensaje||"Sin mensaje de estado")}</p>
            </div>
        `;l+=j(i),p.forEach(d=>{l+=D(d.title,d.tone,g[d.key]||[],"mecanica")}),o.ultimo_analisis&&(l+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${n(o.ultimo_analisis.fecha||"Sin registro")}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Modulos configurados: <span class="font-semibold text-gray-800">${n(o.ultimo_analisis.modulos||"N/A")}</span></p>
                    </div>
                </div>
            `),(o.acciones_pendientes||0)>0&&(l+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta pasteurizadora tiene ${n(o.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        ${b.urls.mechanicalPlans?`
                        <a href="${n(b.urls.mechanicalPlans)}?linea_id=${e.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        `:""}
                    </div>
                </div>
            `),p.length===0&&!(o.acciones_pendientes>0)&&(l+=q("No hay componentes con alertas activas para esta pasteurizadora.")),l+=`
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                ${b.urls.mechanicalAnalyses?`
                <a href="${n(b.urls.mechanicalAnalyses)}?linea_id=${e.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Analisis
                </a>
                `:""}
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `,r.innerHTML=l,t.classList.add("show"),document.body.style.overflow="hidden"}function le(e){const t=document.getElementById("alertModal"),a=document.getElementById("modalTitle"),r=document.getElementById("modalBody"),o=e.estado||{},g=o.conteo_alertas||{},p=P(o),i=W(o),l=Array.isArray(o.pisos)?o.pisos:[],d=o.nivel||"bueno",u=d==="critico"?"bg-red-50 border-l-4 border-red-500":d==="riesgo"?"bg-orange-50 border-l-4 border-orange-500":d==="operativo"?"bg-yellow-50 border-l-4 border-yellow-500":"bg-green-50 border-l-4 border-green-500";a.innerHTML=`Detalle - ${n(e.nombre)}`;let s=`
            <div class="mb-4 p-4 rounded-lg ${u}">
                <h4 class="font-bold text-lg mb-2">Estado: ${n(d.toUpperCase())}</h4>
                <p class="text-gray-700">${n(o.mensaje||"Central Hidraulica sin mensaje de estado")}</p>
            </div>
        `;s+=j(g),i.forEach(f=>{s+=D(f.title,f.tone,p[f.key]||[],"central")}),l.length>0&&(s+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Avance por piso</h4>
                    <div class="space-y-2">
            `,l.forEach(f=>{const m=Math.min(100,Math.max(0,Number(f.porcentaje||0)));s+=`
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">${n(f.label||"Piso")}</span>
                            <span class="text-sm font-bold text-blue-600">${m}%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: ${m}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Revisados: ${f.revisados||0} / ${f.total||0} - Alertas: ${f.alertas||0}</p>
                    </div>
                `}),s+="</div></div>"),o.ultimo_analisis&&(s+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${n(o.ultimo_analisis.fecha||"Sin registro")}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Componente: <span class="font-semibold text-gray-800">${n(o.ultimo_analisis.componente||"N/A")}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Piso: <span class="font-semibold text-gray-800">${n(o.ultimo_analisis.piso||"N/A")}</span></p>
                    </div>
                </div>
            `),(o.acciones_pendientes||0)>0&&(s+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta central tiene ${n(o.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        ${b.urls.centralPlans?`
                        <a href="${n(b.urls.centralPlans)}&linea_id=${e.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        `:""}
                    </div>
                </div>
            `),i.length===0&&!(o.acciones_pendientes>0)&&(s+=q("No hay registros con alertas activas para esta Central Hidraulica.")),s+=`
            <div class="flex flex-wrap justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                ${b.urls.centralAnalyses?`
                <a href="${n(b.urls.centralAnalyses)}?linea_id=${e.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-droplet mr-1"></i> Ver Analisis
                </a>
                <a href="${n(b.urls.centralHistory)}?linea_id=${e.id}" class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-800 transition">
                    <i class="fas fa-history mr-1"></i> Historico
                </a>
                `:""}
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `,r.innerHTML=s,t.classList.add("show"),document.body.style.overflow="hidden"}function N(){document.getElementById("alertModal").classList.remove("show"),document.body.style.overflow=""}document.getElementById("alertModal").addEventListener("click",function(e){e.target===this&&N()});document.addEventListener("keydown",function(e){e.key==="Escape"&&N()});Object.assign(window,{refreshData:M,showPasteurizadoraDetail:ie,showCentralHidraulicaDetail:le,closeModal:N});
