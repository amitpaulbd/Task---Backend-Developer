<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <form method="POST" enctype="multipart/form-data" id="form" action="{{route('calcomm')}}">
                        @csrf
                        <div>
                            <x-label for="file" :value="__('Select CSV File')" />

                            <x-input id="file" class="block mt-1 w-full" type="file" name="file" required />
                        </div>

                        <div class=" mt-4">
                            <x-button>
                                {{ __('Calculate') }}
                            </x-button>
                            <x-button id="btnTest" type="button">
                                {{ __('Test Static Data') }}
                            </x-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 commissions" style="display: none;">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 bg-white border-b border-gray-200">
                <div id="blkResults"></div>
            </div>
        </div>
    </div>

    @section('script')
    <script>
        $(document).ready(function () {
            $("#form").submit(function (event) {
                var form = $('#form')[0];
                var formData = new FormData(form);
                $('#form').find('button:submit').attr('disabled', true);
                let request = $.ajax({
                    url: $("#form").attr('action'),
                    type: "post",
                    data: formData,
                    dataType: "json",
                    contentType: false,
                    processData: false,
                });
                request.done(function (data) {
                    if (data.success === true) {
                        $("#blkResults").html('');
                        $.each(data.commission_data, function (index, value) {
                            $("#blkResults").append(value + '<br>');
                        });
                        $("#blkResults").closest('.commissions').show();
                    } else {
                        alert(data.message);
                    }
                });
                request.fail(function (jqXHR, textStatus) {
                    alert("Fail");
                });
                request.always(function () {
                    $('#form').find('button:submit').removeAttr('disabled');
                });


                event.preventDefault();
                return false;
            });

            $("#btnTest").click(function (event) {
                var _handler = $(this);
                _handler.attr('disabled', true);
                let request = $.ajax({
                    url: $("#form").attr('action'),
                    type: "post",
                    data: {test: 1},
                    dataType: "json",
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    }
                });
                request.done(function (data) {
                    if (data.success === true) {
                        $("#blkResults").html('');
                        $.each(data.commission_data, function (index, value) {
                            $("#blkResults").append(value + '<br>');
                        });
                        $("#blkResults").closest('.commissions').show();
                    } else {
                        alert(data.message);
                    }
                });
                request.fail(function (jqXHR, textStatus) {
                    alert("Fail");
                });
                request.always(function () {
                    _handler.removeAttr('disabled');
                });
            });
        });
    </script>
    @endsection

</x-app-layout>

