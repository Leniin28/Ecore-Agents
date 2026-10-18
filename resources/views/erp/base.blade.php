@extends('erp.layout')
@section('erp-title', $titulo)
@section('erp-content')
<p>Integración ERP en preparación. El catálogo de recursos ya utiliza los productos del SCM.</p>
<a class="button" href="{{ route('erp.recursos') }}">Consultar recursos</a>
@endsection
