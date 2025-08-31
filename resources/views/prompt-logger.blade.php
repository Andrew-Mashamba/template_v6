@extends('layouts.app')

@section('title', 'Prompt Chain Logger')

@section('content')
    <div class="min-h-screen bg-gray-100">
        @livewire('prompt-chain-logger')
    </div>
@endsection

@push('styles')
<style>
    /* Custom styles for the logger */
    .bg-gray-650 {
        background-color: #4a5568;
    }
    
    /* Scrollbar styling for dark theme */
    ::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }
    
    ::-webkit-scrollbar-track {
        background: #2d3748;
    }
    
    ::-webkit-scrollbar-thumb {
        background: #4a5568;
        border-radius: 4px;
    }
    
    ::-webkit-scrollbar-thumb:hover {
        background: #718096;
    }
</style>
@endpush