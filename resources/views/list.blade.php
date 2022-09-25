@extends('header')

@section('content')
    <section class="py-5 roadmap catalog-top d-none d-sm-block">
        <div class="container">


            <div class="row mt-5 course-categories">
                <div class="col-lg-12 col-12 mb-0 mb-md-3">
                    <h4 class="header-cate ms-2">
                        Katalog Bootcamp
                    </h4>
                </div>
            </div>
            <div class="row gy-4">
                @foreach ($bootcamps as $bootcamp)
                    <div class="col-lg-4 col-md-6 col-12">
                        <div class="course-card-responsive">
                            <div class="d-flex align-items-center align-items-md-start flex-row flex-md-column gap-md-4">

                                <img src="{{ asset('img/' . $bootcamp->thumbnail) }}" class="thumbnail-course"
                                    alt="Intensive Bootcamp Web Development dengan Laravel" />

                                <div class="course-detail">
                                    <a href="#" class="course-name line-clamp">
                                        {{ $bootcamp->title }}
                                    </a>
                                    <div class="d-flex mt-2 align-items-center gap-1">
                                        Rp <span class="">{{ number_format($bootcamp->price, 2, ',', '.') }}</span>
                                    </div>
                                    <a href="{{ route('checkout', $bootcamp->id) }}" class="link-course stretched-link">
                                    </a>
                                </div>
                            </div>
                            <div class="course-footer mt-auto">
                                <a href="{{ route('checkout', $bootcamp->id) }}">
                                    <button type="button" class="btn btn-primary">Checkout</button>
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>

        </div>
        <div class="modal fade" data-bs-backdrop="static" data-bs-keyboard="false" id="modalTrailerCourse" tabindex="-1"
            aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header text-black d-flex align-items-center gap-3">
                        <h6 class="modal-title" id="staticBackdropLabel">
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                            onclick="checkScroll(false)"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="embed-responsive embed-responsive-16by9 video-iframe">
                            <div class="plyr__video-embed" id="player">
                                <iframe allowfullscreen allowtransparency allow="autoplay" frameborder="0"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
