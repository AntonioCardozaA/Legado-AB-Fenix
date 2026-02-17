@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0">HISTORICO DE REVISADOS</h4>
        </div>
        <div class="card-body">
            <!-- Tabla de componentes -->
            <div class="table-responsive mb-5">
                <table class="table table-bordered table-hover">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50%">COMPONENTE</th>
                            <th class="text-center">CANTIDAD TOTAL</th>
                            <th class="text-center">CANTIDAD REVISADAS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>REDUCTORES CHICOS</td>
                            <td class="text-center">15</td>
                            <td class="text-center">15</td>
                        </tr>
                        <tr>
                            <td>REDUCTORES GRANDES</td>
                            <td class="text-center">15</td>
                            <td class="text-center">6</td>
                        </tr>
                        <tr>
                            <td>BUJES DE BAQUELITA Y ESPIGA</td>
                            <td class="text-center">15</td>
                            <td class="text-center">8</td>
                        </tr>
                        <tr>
                            <td>GUIAS SUPERIORES</td>
                            <td class="text-center">15</td>
                            <td class="text-center">15</td>
                        </tr>
                        <tr>
                            <td>GUIAS INFERIORES</td>
                            <td class="text-center">15</td>
                            <td class="text-center">2</td>
                        </tr>
                        <tr>
                            <td>GUIAS DE RETORNO</td>
                            <td class="text-center">15</td>
                            <td class="text-center">2</td>
                        </tr>
                        <tr>
                            <td>CATARINAS</td>
                            <td class="text-center">15</td>
                            <td class="text-center">1</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Histograma -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">Histograma</h5>
                    
                    <!-- CATARINAS -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>CATARINAS</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-danger" 
                                         role="progressbar" 
                                         style="width: {{ (1/15)*100 }}%;" 
                                         aria-valuenow="1" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        1/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GUIAS DE RETORNO -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>GUIAS DE RETORNO</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-warning" 
                                         role="progressbar" 
                                         style="width: {{ (2/15)*100 }}%;" 
                                         aria-valuenow="2" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        2/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GUIAS INFERIORES -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>GUIAS INFERIORES</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-warning" 
                                         role="progressbar" 
                                         style="width: {{ (2/15)*100 }}%;" 
                                         aria-valuenow="2" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        2/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- GUIAS SUPERIORES -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>GUIAS SUPERIORES</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: {{ (15/15)*100 }}%;" 
                                         aria-valuenow="15" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        15/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BUJES DE BAQUELITA Y ESPIGA -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>BUJES DE BAQUELITA Y ESPIGA</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-info" 
                                         role="progressbar" 
                                         style="width: {{ (8/15)*100 }}%;" 
                                         aria-valuenow="8" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        8/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REDUCTORES GRANDES -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>REDUCTORES GRANDES</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-primary" 
                                         role="progressbar" 
                                         style="width: {{ (6/15)*100 }}%;" 
                                         aria-valuenow="6" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        6/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- REDUCTORES CHICOS -->
                    <div class="mb-2">
                        <div class="row">
                            <div class="col-md-3">
                                <strong>REDUCTORES CHICOS</strong>
                            </div>
                            <div class="col-md-9">
                                <div class="progress" style="height: 25px;">
                                    <div class="progress-bar bg-success" 
                                         role="progressbar" 
                                         style="width: {{ (15/15)*100 }}%;" 
                                         aria-valuenow="15" 
                                         aria-valuemin="0" 
                                         aria-valuemax="15">
                                        15/15
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Línea de progreso numérica -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex justify-content-between" style="max-width: 900px; margin-left: 25%;">
                        <span>0</span>
                        <span>2</span>
                        <span>4</span>
                        <span>6</span>
                        <span>8</span>
                        <span>10</span>
                        <span>12</span>
                        <span>14</span>
                        <span>16</span>
                    </div>
                </div>
            </div>

            <!-- Botón Volver -->
            <div class="mt-4">
                <a href="{{ route('plan-accion.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> VOLVER
                </a>
                
                <!-- Botones adicionales -->
                <button class="btn btn-success btn-sm">
                    <i class="fas fa-sync-alt"></i> Actualizar
                </button>
                <button class="btn btn-info btn-sm">
                    <i class="fas fa-chart-bar"></i> Ver Detalle
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    vertical-align: middle;
}

.table td {
    vertical-align: middle;
}

.table td:first-child {
    font-weight: 500;
}

.progress {
    border-radius: 4px;
    background-color: #e9ecef;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.1);
}

.progress-bar {
    line-height: 25px;
    font-size: 12px;
    font-weight: 600;
    text-align: right;
    padding-right: 10px;
}

/* Colores personalizados para las barras */
.bg-danger { background-color: #dc3545; }
.bg-warning { background-color: #ffc107; }
.bg-success { background-color: #28a745; }
.bg-info { background-color: #17a2b8; }
.bg-primary { background-color: #007bff; }

/* Hover effect en la tabla */
.table-hover tbody tr:hover {
    background-color: #f5f5f5;
}

/* Estilo para la línea numérica */
.justify-content-between span {
    font-size: 14px;
    color: #6c757d;
    font-weight: 500;
}
</style>
@endsection