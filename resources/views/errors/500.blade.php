@include('errors.layout', [
    'title'       => 'Server error',
    'code'        => '500',
    'codeColor'   => 'text-rose-400',
    'heading'     => 'Something went wrong',
    'description' => "We hit an unexpected error. The team has been notified.",
])
