@extends('layouts.app')

@section('title', 'Reporte Detallado de Lavadoras - ' . ($lineaId ? $reporte['linea']->nombre : 'General'))

@section('content')

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>

:root{
--primary-blue:#3b82f6;
--success-green:#10b981;
--warning-yellow:#f59e0b;
--danger-red:#ef4444;
--dark:#0f172a;
--dark-light:#1e293b;
--border:#e2e8f0;
--background:#f8fafc;
}

.reporte-container{
max-width:1400px;
margin:0 auto;
padding:24px;
}

/* HEADER */

.industrial-header{
background:linear-gradient(135deg,var(--dark),var(--dark-light));
border-radius:24px;
padding:32px 40px;
margin-bottom:32px;
color:white;
box-shadow:0 25px 50px -12px rgba(0,0,0,0.5);
}

.back-link{
display:inline-flex;
align-items:center;
gap:8px;
color:#94a3b8;
text-decoration:none;
font-weight:500;
font-size:14px;
margin-bottom:20px;
padding:8px 16px;
background:rgba(255,255,255,0.05);
border-radius:40px;
}

.header-title{
display:flex;
align-items:center;
gap:20px;
}

.title-icon{
width:70px;
height:70px;
background:linear-gradient(135deg,var(--primary-blue),#2563eb);
border-radius:20px;
display:flex;
align-items:center;
justify-content:center;
font-size:32px;
color:white;
}

/* BOTONES */

.btn-modal{
padding:8px 16px;
border-radius:8px;
background:#3b82f6;
color:white;
border:none;
font-size:13px;
cursor:pointer;
margin-right:6px;
}

.btn-modal:hover{
background:#2563eb;
}

/* MODAL */

.modal-overlay{
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.6);
display:none;
align-items:center;
justify-content:center;
z-index:9999;
}

.modal-content{
background:white;
width:95%;
max-width:1000px;
border-radius:20px;
padding:25px;
max-height:90vh;
overflow-y:auto;
}

.modal-header{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:20px;
}

.modal-close{
background:none;
border:none;
font-size:22px;
cursor:pointer;
}

/* TABS */

.modal-tabs{
display:flex;
gap:10px;
margin-bottom:20px;
}

.modal-tabs button{
padding:8px 14px;
border:none;
border-radius:8px;
background:#e2e8f0;
cursor:pointer;
}

.modal-tabs button:hover{
background:#cbd5f5;
}

.tab-content{
display:none;
}

.tab-content.active{
display:block;
}

/* TARJETAS */

.stat-grid{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:20px;
}

.stat-card{
background:#f9fafb;
border-radius:15px;
padding:20px;
text-align:center;
border:1px solid #e5e7eb;
}

table{
width:100%;
border-collapse:collapse;
}

table th{
background:#f1f5f9;
padding:8px;
}

table td{
padding:8px;
border-bottom:1px solid #e5e7eb;
}

</style>


<div class="reporte-container">

<div class="industrial-header">

<a href="{{ route('reportes.index', ['tipo' => $tipoEquipo]) }}" class="back-link">
Volver a Reportes
</a>

<div class="header-title">

<div class="title-icon">
<i class="fas fa-washing-machine"></i>
</div>

<div>

<h1>
@if($lineaId)
Reporte Detallado - {{ $reporte['linea']->nombre }}
@else
Reporte General de Lavadoras
@endif
</h1>

<p>
Período: {{ $fechaInicio->format('d/m/Y') }} - {{ $fechaFin->format('d/m/Y') }}
</p>

</div>

</div>
</div>


@if($lineaId)

@include('reportes.partials.reporte-linea-lavadora',['reporte'=>$reporte])

@else

@foreach($reporte['lineas'] as $reporteLinea)

<div class="mb-8">
@include('reportes.partials.reporte-linea-lavadora',['reporte'=>$reporteLinea])
</div>

@endforeach

@endif

</div>


<!-- MODAL DASHBOARD -->

<div id="modalLavadora" class="modal-overlay">

<div class="modal-content">

<div class="modal-header">
<h2 id="modalTitulo">Detalles</h2>
<button onclick="cerrarModal()" class="modal-close">✕</button>
</div>

<div class="modal-tabs">
<button onclick="mostrarTab('analisis')">Análisis</button>
<button onclick="mostrarTab('historial')">Historial</button>
<button onclick="mostrarTab('componentes')">Componentes</button>
<button onclick="mostrarTab('grafica')">Tendencia</button>
</div>

<div id="tab-analisis" class="tab-content"></div>
<div id="tab-historial" class="tab-content"></div>
<div id="tab-componentes" class="tab-content"></div>

<div id="tab-grafica" class="tab-content">
<canvas id="graficaTendencia"></canvas>
</div>

</div>
</div>


<script>

function abrirModal(titulo){
document.getElementById("modalTitulo").innerText=titulo;
document.getElementById("modalLavadora").style.display="flex";
}

function cerrarModal(){
document.getElementById("modalLavadora").style.display="none";
}

function mostrarTab(tab){

document.querySelectorAll(".tab-content").forEach(t=>{
t.classList.remove("active");
});

document.getElementById("tab-"+tab).classList.add("active");

}


/* TAB ANALISIS */

function verTodosAnalisis(datos){

let tabla=`
<table>
<tr>
<th>Fecha</th>
<th>Componente</th>
<th>Estado</th>
</tr>
`;

datos.forEach(a=>{
tabla+=`
<tr>
<td>${a.fecha_analisis}</td>
<td>${a.componente?.nombre ?? ''}</td>
<td>${a.estado}</td>
</tr>
`;
});

tabla+=`</table>`;

document.getElementById("tab-analisis").innerHTML=tabla;

abrirModal("Todos los análisis");

mostrarTab("analisis");

}


/* HISTORIAL */

function verHistorial(datos){

let tabla=`<table>
<tr>
<th>Fecha</th>
<th>Evento</th>
</tr>
`;

datos.forEach(h=>{
tabla+=`
<tr>
<td>${h.fecha}</td>
<td>${h.descripcion}</td>
</tr>
`;
});

tabla+=`</table>`;

document.getElementById("tab-historial").innerHTML=tabla;

abrirModal("Historial");

mostrarTab("historial");

}


/* COMPONENTES */

function verComponentes(datos){

let tabla=`<table>
<tr>
<th>Componente</th>
<th>Total análisis</th>
<th>Estado</th>
</tr>
`;

datos.forEach(c=>{
tabla+=`
<tr>
<td>${c.nombre}</td>
<td>${c.total_analisis}</td>
<td>${c.ultimo_estado ?? 'Sin datos'}</td>
</tr>
`;
});

tabla+=`</table>`;

document.getElementById("tab-componentes").innerHTML=tabla;

abrirModal("Componentes");

mostrarTab("componentes");

}


/* GRAFICA */

function mostrarGrafica(datos){

abrirModal("Tendencia");

mostrarTab("grafica");

const ctx=document.getElementById('graficaTendencia');

new Chart(ctx,{
type:'line',
data:{
labels:datos.meses,
datasets:[{
label:'Daños',
data:datos.valores,
borderColor:'#ef4444',
backgroundColor:'rgba(239,68,68,0.2)',
fill:true
}]
},
options:{
responsive:true
}
});

}

</script>

@endsection