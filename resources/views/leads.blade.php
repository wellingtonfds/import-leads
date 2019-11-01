@extends('template.master')
@section('content')
<div class="row">
     <section class="col-lg-12 mt-2">
        @if (session('status'))
        <div class="alert alert-success display-none" id="alert-success" role="alert">
            {{session('status')}}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        @endif
        {{-- <div class="alert alert-danger display-none" id="alert-error" role="alert">
            Ops!! Algo deu errado. Tente novamente
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div> --}}
    </section>
    <section class="col-lg-12 mt-2 text-white">
        <div class="card bg-secondary">
            <div class="card-header">
                Importação de arquivo CSV para leads
            </div>
            <div class="card-body">
                <form action="{{ route('admin.import') }} " method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Arquivo</label>
                        <input type="file" class="form-control-file" name="fileImport">
                    </div>
                    <div class="form-group">
                        <label>Origem</label>
                        <select name="type-import-file" id="" class="form-control">
                            <option value="sharp-spring-file">SharpSpring - Leads</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="Upload" class="btn btn-primary">

                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </section>
    <section class="col-lg-12 mt-2 text-white">
        <div class="card bg-black">
            <div class="card-header">
                Importação de arquivo CSV para Oportunidades
            </div>
            <div class="card-body">
                <form action="{{ route('admin.import') }} " method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Arquivo</label>
                        <input type="file" class="form-control-file" name="fileImport">
                    </div>
                    <div class="form-group">
                        <label>Marca</label>
                        <select class="form-control" name="brand">
                            <option value="5">Suav</option>
                            <option value="4">Quisto</option>
                            <option value="1">Acquazero</option>
                            <option value="2">Encontre sua viagem</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Origem</label>
                        <select name="type-import-file" id="" class="form-control">
                            <option value="sharp-spring-opp">SharpSpring - Opportunities</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" name="deleteAllOpp" id="customSwitch2">
                            <label class="custom-control-label" for="customSwitch2">Apagar todas as
                                oportunidades</label>
                        </div>
                        <input type="submit" value="Upload" class="btn btn-primary">
                    </div>
                    @csrf
                </form>
            </div>
        </div>
    </section>
</div>
@endsection
