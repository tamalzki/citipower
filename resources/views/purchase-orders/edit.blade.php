@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')

@include('purchase-orders.create', ['__editing' => true])

@endsection

