const l=JSON.parse(document.getElementById("pasteurDashboardData").textContent);l.pasteurizadorasData;const f=l.fallasPorLineaPasteurizadora,A=l.componentesDanadosPasteurizadora,z=l.planesAccionDashboardPasteurizadora,y=l.avanceRevisionPasteurizadora;l.centralesHidraulicasData;const m=l.fallasPorLineaCentralHidraulica,$=l.planesAccionDashboardCentralHidraulica,h=l.avanceRevisionCentralHidraulica;document.addEventListener("DOMContentLoaded",function(){B(),W(),j()});function B(){const e=document.getElementById(l.fallasCanvasId);if(e){const a=e.getContext("2d");new Chart(a,{type:"bar",data:{labels:f.map(r=>r.linea),datasets:[{label:"Críticos",data:f.map(r=>r.criticos||0),backgroundColor:"rgba(239, 68, 68, 0.9)",borderColor:"#dc2626",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Requiere revisión",data:f.map(r=>r.requiere_revision||0),backgroundColor:"rgba(245, 158, 11, 0.9)",borderColor:"#d97706",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Severo / Moderado",data:f.map(r=>r.desgaste||0),backgroundColor:"rgba(249, 115, 22, 0.88)",borderColor:"#ea580c",borderWidth:2,borderRadius:12,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,stacked:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",padding:8}},x:{stacked:!0,grid:{display:!1,drawBorder:!1},ticks:{font:{size:13,weight:600},color:"#334155",padding:8}}},plugins:{legend:{display:!0,position:"top",labels:{usePointStyle:!0,padding:18,font:{size:12,weight:"bold"},color:"#334155"}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#3b82f6",borderWidth:2,padding:14,callbacks:{label:function(r){return`${r.dataset.label}: ${r.raw}`},footer:function(r){const o=f[r[0]?.dataIndex];return o?`Total: ${o.total_fallas||0}`:""}}}}}})}S(),R(),L(),D(),I(),H()}function S(){const e=document.getElementById("componentesPasteurizadoraChart");if(!e)return null;const a=Array.isArray(A)?A.filter(r=>Number(r.total_danios||0)>0):[];return new Chart(e.getContext("2d"),{type:"doughnut",data:{labels:a.map(r=>r.componente||"Sin componente"),datasets:[{data:a.map(r=>Number(r.total_danios||0)),backgroundColor:["rgba(239, 68, 68, 0.9)","rgba(245, 158, 11, 0.9)","rgba(16, 185, 129, 0.9)","rgba(59, 130, 246, 0.9)","rgba(139, 92, 246, 0.9)","rgba(236, 72, 153, 0.9)"],borderColor:["#dc2626","#d97706","#059669","#2563eb","#7c3aed","#db2777"],borderWidth:3,borderRadius:8,hoverBorderWidth:5,hoverOffset:12,spacing:3}]},options:{responsive:!0,maintainAspectRatio:!1,cutout:"62%",plugins:{legend:{position:"right",labels:{color:"#334155",padding:16,usePointStyle:!0,pointStyle:"circle",boxWidth:10,font:{size:12,weight:600,family:"'Inter', sans-serif"}}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#f59e0b",borderWidth:2,padding:14,callbacks:{label:r=>{const o=r.dataset.data.reduce((b,i)=>b+Number(i||0),0),t=Number(r.raw||0),p=o>0?(t/o*100).toFixed(1):"0.0";return[`Daños: ${t}`,`${p}%`]}}}}}})}function R(){const e=document.getElementById("planesPasteurizadoraChart");if(!e)return null;const a=z?.resumen||{};return new Chart(e.getContext("2d"),{type:"doughnut",data:{labels:["Pendientes críticos","Activos programados","Completados"],datasets:[{data:[Number(a.pendientes||0),Number(a.programados||0),Number(a.completados||0)],backgroundColor:["rgba(239, 68, 68, 0.92)","rgba(245, 158, 11, 0.88)","rgba(16, 185, 129, 0.88)"],borderColor:["#dc2626","#d97706","#059669"],borderWidth:2,hoverOffset:10}]},options:{responsive:!0,maintainAspectRatio:!1,cutout:"68%",plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#3b82f6",borderWidth:2,padding:14,callbacks:{label:r=>`${r.label}: ${r.raw}`}}}},plugins:[{id:"pasteurPlanesCenterLabel",beforeDraw(r){const{ctx:o}=r;o.save(),o.textAlign="center",o.textBaseline="middle",o.fillStyle="#0f172a",o.font="700 24px sans-serif",o.fillText(`${Number(a.avance||0)}%`,r.width/2,r.height/2-6),o.fillStyle="#64748b",o.font="600 11px sans-serif",o.fillText("avance global",r.width/2,r.height/2+16),o.restore()}}]})}function L(){const e=document.getElementById("avanceRevisionPasteurizadoraChart");if(!e)return null;const a=Array.isArray(y?.labels)?y.labels:[],r=Array.isArray(y?.porcentajes)?y.porcentajes.map(o=>Number(o||0)):[];return new Chart(e.getContext("2d"),{type:"bar",data:{labels:a,datasets:[{label:"Avance de revisión mecánica",data:r,backgroundColor:r.map(o=>o>=90?"rgba(16, 185, 129, 0.88)":o>=60?"rgba(245, 158, 11, 0.88)":"rgba(249, 115, 22, 0.88)"),borderColor:r.map(o=>o>=90?"#059669":o>=60?"#d97706":"#ea580c"),borderWidth:2,borderRadius:10,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,max:100,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",callback:o=>`${o}%`}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155"}}},plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#e0e7ff",borderColor:"#10b981",borderWidth:2,padding:14,callbacks:{label:o=>`${o.raw}% revisado`}}}}})}function D(){const e=document.getElementById("fallasCentralHidraulicaChart");return e?new Chart(e.getContext("2d"),{type:"bar",data:{labels:m.map(a=>a.linea),datasets:[{label:"Criticos",data:m.map(a=>a.criticos||0),backgroundColor:"rgba(239, 68, 68, 0.9)",borderColor:"#dc2626",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Requiere revision",data:m.map(a=>a.requiere_revision||0),backgroundColor:"rgba(245, 158, 11, 0.9)",borderColor:"#d97706",borderWidth:2,borderRadius:12,borderSkipped:!1},{label:"Severo / Moderado",data:m.map(a=>a.desgaste||0),backgroundColor:"rgba(20, 184, 166, 0.86)",borderColor:"#0f766e",borderWidth:2,borderRadius:12,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,stacked:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",padding:8}},x:{stacked:!0,grid:{display:!1,drawBorder:!1},ticks:{font:{size:13,weight:600},color:"#334155",padding:8}}},plugins:{legend:{display:!0,position:"top",labels:{usePointStyle:!0,padding:18,font:{size:12,weight:"bold"},color:"#334155"}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#ccfbf1",borderColor:"#0f766e",borderWidth:2,padding:14,callbacks:{label:a=>`${a.dataset.label}: ${a.raw}`,footer:function(a){const r=m[a[0]?.dataIndex];return r?`Total: ${r.total_fallas||0}`:""}}}}}}):null}function I(){const e=document.getElementById("planesCentralHidraulicaChart");if(!e)return null;const a=Array.isArray($?.por_linea)?$.por_linea:[];return new Chart(e.getContext("2d"),{type:"bar",data:{labels:a.map(r=>r.linea||"N/A"),datasets:[{label:"Abiertos",data:a.map(r=>Number(r.abiertos||0)),backgroundColor:"rgba(239, 68, 68, 0.86)",borderColor:"#dc2626",borderWidth:2,borderRadius:10,borderSkipped:!1},{label:"Completados",data:a.map(r=>Number(r.completados||0)),backgroundColor:"rgba(20, 184, 166, 0.86)",borderColor:"#0f766e",borderWidth:2,borderRadius:10,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",precision:0}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155"}}},plugins:{legend:{position:"top",labels:{usePointStyle:!0,padding:18,font:{size:12,weight:"bold"},color:"#334155"}},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#ccfbf1",borderColor:"#0f766e",borderWidth:2,padding:14}}}})}function H(){const e=document.getElementById("avanceRevisionCentralHidraulicaChart");if(!e)return null;const a=Array.isArray(h?.labels)?h.labels:[],r=Array.isArray(h?.porcentajes)?h.porcentajes.map(o=>Number(o||0)):[];return new Chart(e.getContext("2d"),{type:"bar",data:{labels:a,datasets:[{label:"Avance de revision hidraulica",data:r,backgroundColor:r.map(o=>o>=90?"rgba(20, 184, 166, 0.88)":o>=60?"rgba(245, 158, 11, 0.88)":"rgba(249, 115, 22, 0.88)"),borderColor:r.map(o=>o>=90?"#0f766e":o>=60?"#d97706":"#ea580c"),borderWidth:2,borderRadius:10,borderSkipped:!1}]},options:{responsive:!0,maintainAspectRatio:!1,scales:{y:{beginAtZero:!0,max:100,grid:{color:"rgba(0, 0, 0, 0.05)",drawBorder:!1,drawTicks:!1},ticks:{font:{size:12,weight:600},color:"#64748b",callback:o=>`${o}%`}},x:{grid:{display:!1,drawBorder:!1},ticks:{font:{size:12,weight:600},color:"#334155"}}},plugins:{legend:{display:!1},tooltip:{backgroundColor:"rgba(15, 23, 42, 0.95)",titleColor:"#ffffff",bodyColor:"#ccfbf1",borderColor:"#0f766e",borderWidth:2,padding:14,callbacks:{label:o=>`${o.raw}% revisado`}}}}})}function k(){window.location.reload()}function j(){setInterval(()=>{k()},3e5)}function W(){document.querySelectorAll(".lavadora-carousel").forEach(e=>{const a=e.querySelectorAll(".carousel-slide"),r=e.querySelector(".carousel-prev"),o=e.querySelector(".carousel-next"),t=e.querySelectorAll(".carousel-dot");let p=0;function b(n){a.forEach((g,d)=>{g.classList.toggle("active",d===n)}),t.forEach((g,d)=>{g.classList.toggle("active",d===n)}),p=n}function i(){a.length<=1||b((p+1)%a.length)}function c(){a.length<=1||b((p-1+a.length)%a.length)}o&&o.addEventListener("click",i),r&&r.addEventListener("click",c),t.forEach(n=>{n.addEventListener("click",()=>{const g=parseInt(n.dataset.index,10);isNaN(g)||b(g)})}),a.length>1&&setInterval(i,6e3)})}function s(e){return String(e??"").replace(/[&<>"']/g,function(a){return{"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[a]})}function M(e){if(!e)return"Sin fecha";const a=new Date(e);return Number.isNaN(a.getTime())?s(e):a.toLocaleDateString("es-MX")}function C(e){const a=e.analisis_por_estado||{};return{critico:Array.isArray(a.critico)?a.critico:Array.isArray(e.analisis_criticos)?e.analisis_criticos:[],severo:Array.isArray(a.severo)?a.severo:[],moderado:Array.isArray(a.moderado)?a.moderado:[],revision:Array.isArray(a.revision)?a.revision:Array.isArray(e.analisis_revision)?e.analisis_revision:[]}}function _(e){const a=C(e);return[{key:"critico",title:"Requiere cambio",tone:"critico"},{key:"severo",title:"Daño severo",tone:"severo"},{key:"moderado",title:"Daño moderado",tone:"moderado"},{key:"revision",title:"Requiere revision",tone:"revision"}].filter(r=>Array.isArray(a[r.key])&&a[r.key].length>0)}function T(e,a){return a==="central"?[e.piso?`Piso: ${e.piso}`:null,e.lado||null].filter(Boolean).join(" · "):[e.modulo?`Modulo: ${e.modulo}`:null,e.nivel?`Nivel: ${e.nivel}`:null,e.lado||null].filter(Boolean).join(" · ")}function P(e,a,r,o="mecanica"){if(!Array.isArray(r)||r.length===0)return"";const p={critico:"bg-red-100 text-red-700",severo:"bg-orange-100 text-orange-700",moderado:"bg-amber-100 text-amber-700",revision:"bg-yellow-100 text-yellow-700"}[a]||"bg-slate-100 text-slate-700",b=r.map(i=>{const c="/images/icono_pas.png",n=i.image||i.fallback_image||c,g=T(i,o),d=i.componente_nombre||i.componente?.nombre||"Componente",u=i.componentes_revisados_label||"",v=i.fecha_formateada||M(i.fecha_analisis),w=i.numero_orden?`Orden: ${i.numero_orden}`:null;return`
                <div class="bg-white rounded-lg p-3 border border-gray-200">
                    <div class="flex justify-between items-start gap-3">
                        <div class="min-w-0 flex-1">
                            <div class="componente-header">
                                <div class="componente-icono">
                                    <img src="${s(n)}" class="w-8 h-8 object-contain" onerror="this.src='${s(i.fallback_image||c)}'">
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="componente-nombre">${s(d)}</div>
                                    ${u?`<p class="text-sm font-semibold text-gray-700 mt-1">${s(u)}</p>`:""}
                                </div>
                            </div>
                            ${g?`<p class="text-sm text-gray-600 mt-2">${s(g)}</p>`:""}
                            <p class="text-xs text-gray-500 mt-1">
                                Fecha: ${s(v)}${w?` · ${s(w)}`:""}
                            </p>
                        </div>
                        <span class="px-2 py-1 rounded text-xs font-semibold ${p}">
                            ${s(i.estado_label||i.estado||e)}
                        </span>
                    </div>
                    <p class="text-sm text-gray-700 mt-2">
                        ${s(i.actividad||"Sin descripcion")}
                    </p>
                </div>
            `}).join("");return`
            <div class="mb-4">
                <div class="flex items-center justify-between gap-3 mb-2">
                    <h4 class="font-bold text-gray-800">${s(e)}</h4>
                    <span class="severity-pill ${a}">${r.length}</span>
                </div>
                <div class="space-y-3">
                    ${b}
                </div>
            </div>
        `}function E(e){return Object.values(e||{}).reduce((r,o)=>r+Number(o||0),0)<=0?"":`
            <div class="mb-4 flex flex-wrap gap-2">
                ${Number(e.critico||0)>0?`<span class="severity-pill critico">${Number(e.critico||0)} requiere cambio</span>`:""}
                ${Number(e.severo||0)>0?`<span class="severity-pill severo">${Number(e.severo||0)} severo</span>`:""}
                ${Number(e.moderado||0)>0?`<span class="severity-pill moderado">${Number(e.moderado||0)} moderado</span>`:""}
                ${Number(e.revision||0)>0?`<span class="severity-pill revision">${Number(e.revision||0)} revision</span>`:""}
            </div>
        `}function N(e){return`
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200 text-sm text-slate-600">
                ${s(e)}
            </div>
        `}function q(e){const a=document.getElementById("alertModal"),r=document.getElementById("modalTitle"),o=document.getElementById("modalBody"),t=e.estado||{},p=C(t),b=_(t),i=t.conteo_alertas||{};r.innerHTML=`Detalle - ${s(e.nombre)}`;let c=`
            <div class="mb-4 p-4 rounded-lg ${t.nivel==="critico"?"bg-red-50 border-l-4 border-red-500":t.nivel==="riesgo"?"bg-orange-50 border-l-4 border-orange-500":t.nivel==="operativo"?"bg-yellow-50 border-l-4 border-yellow-500":"bg-green-50 border-l-4 border-green-500"}">
                <h4 class="font-bold text-lg mb-2">Estado: ${s((t.nivel||"bueno").toUpperCase())}</h4>
                <p class="text-gray-700">${s(t.mensaje||"Sin mensaje de estado")}</p>
            </div>
        `;c+=E(i),b.forEach(n=>{c+=P(n.title,n.tone,p[n.key]||[],"mecanica")}),t.ultimo_analisis&&(c+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${s(t.ultimo_analisis.fecha||"Sin registro")}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Modulos configurados: <span class="font-semibold text-gray-800">${s(t.ultimo_analisis.modulos||"N/A")}</span></p>
                    </div>
                </div>
            `),(t.acciones_pendientes||0)>0&&(c+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta pasteurizadora tiene ${s(t.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        ${l.urls.mechanicalPlans?`
                        <a href="${s(l.urls.mechanicalPlans)}?linea_id=${e.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        `:""}
                    </div>
                </div>
            `),b.length===0&&!(t.acciones_pendientes>0)&&(c+=N("No hay componentes con alertas activas para esta pasteurizadora.")),c+=`
            <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                ${l.urls.mechanicalAnalyses?`
                <a href="${s(l.urls.mechanicalAnalyses)}?linea_id=${e.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-chart-line mr-1"></i> Ver Analisis
                </a>
                `:""}
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `,o.innerHTML=c,a.classList.add("show"),document.body.style.overflow="hidden"}function O(e){const a=document.getElementById("alertModal"),r=document.getElementById("modalTitle"),o=document.getElementById("modalBody"),t=e.estado||{},p=t.conteo_alertas||{},b=C(t),i=_(t),c=Array.isArray(t.pisos)?t.pisos:[],n=t.nivel||"bueno",g=n==="critico"?"bg-red-50 border-l-4 border-red-500":n==="riesgo"?"bg-orange-50 border-l-4 border-orange-500":n==="operativo"?"bg-yellow-50 border-l-4 border-yellow-500":"bg-green-50 border-l-4 border-green-500";r.innerHTML=`Detalle - ${s(e.nombre)}`;let d=`
            <div class="mb-4 p-4 rounded-lg ${g}">
                <h4 class="font-bold text-lg mb-2">Estado: ${s(n.toUpperCase())}</h4>
                <p class="text-gray-700">${s(t.mensaje||"Central Hidraulica sin mensaje de estado")}</p>
            </div>
        `;d+=E(p),i.forEach(u=>{d+=P(u.title,u.tone,b[u.key]||[],"central")}),c.length>0&&(d+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Avance por piso</h4>
                    <div class="space-y-2">
            `,c.forEach(u=>{const v=Math.min(100,Math.max(0,Number(u.porcentaje||0)));d+=`
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-gray-800">${s(u.label||"Piso")}</span>
                            <span class="text-sm font-bold text-blue-600">${v}%</span>
                        </div>
                        <div class="progress-track">
                            <div class="progress-fill" style="width: ${v}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">Revisados: ${u.revisados||0} / ${u.total||0} - Alertas: ${u.alertas||0}</p>
                    </div>
                `}),d+="</div></div>"),t.ultimo_analisis&&(d+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Ultimo analisis</h4>
                    <div class="bg-white rounded-lg p-3 border border-gray-200">
                        <p class="text-sm text-gray-600">Fecha: <span class="font-semibold text-gray-800">${s(t.ultimo_analisis.fecha||"Sin registro")}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Componente: <span class="font-semibold text-gray-800">${s(t.ultimo_analisis.componente||"N/A")}</span></p>
                        <p class="text-sm text-gray-600 mt-1">Piso: <span class="font-semibold text-gray-800">${s(t.ultimo_analisis.piso||"N/A")}</span></p>
                    </div>
                </div>
            `),(t.acciones_pendientes||0)>0&&(d+=`
                <div class="mb-4">
                    <h4 class="font-bold text-gray-800 mb-2">Acciones Pendientes</h4>
                    <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                        <p class="text-yellow-800">Esta central tiene ${s(t.acciones_pendientes)} accion(es) pendiente(s) en el plan de accion.</p>
                        ${l.urls.centralPlans?`
                        <a href="${s(l.urls.centralPlans)}&linea_id=${e.id}" class="mt-2 inline-block text-blue-600 text-sm hover:underline">
                            <i class="fas fa-arrow-right mr-1"></i> Ver Plan de Accion
                        </a>
                        `:""}
                    </div>
                </div>
            `),i.length===0&&!(t.acciones_pendientes>0)&&(d+=N("No hay registros con alertas activas para esta Central Hidraulica.")),d+=`
            <div class="flex flex-wrap justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                ${l.urls.centralAnalyses?`
                <a href="${s(l.urls.centralAnalyses)}?linea_id=${e.id}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    <i class="fas fa-droplet mr-1"></i> Ver Analisis
                </a>
                <a href="${s(l.urls.centralHistory)}?linea_id=${e.id}" class="px-4 py-2 bg-slate-700 text-white rounded hover:bg-slate-800 transition">
                    <i class="fas fa-history mr-1"></i> Historico
                </a>
                `:""}
                <button onclick="closeModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400 transition">
                    Cerrar
                </button>
            </div>
        `,o.innerHTML=d,a.classList.add("show"),document.body.style.overflow="hidden"}function x(){document.getElementById("alertModal").classList.remove("show"),document.body.style.overflow=""}document.getElementById("alertModal").addEventListener("click",function(e){e.target===this&&x()});document.addEventListener("keydown",function(e){e.key==="Escape"&&x()});Object.assign(window,{refreshData:k,showPasteurizadoraDetail:q,showCentralHidraulicaDetail:O,closeModal:x});
