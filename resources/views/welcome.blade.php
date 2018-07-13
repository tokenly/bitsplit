@extends('app')

@section('content')
    
    @include('welcome.hero')

    <section id="about" class="welcome-section">
        <div class="welcome-section-content">
            <div class="centered welcome-section-heading">
                <header>Customized Token Distributions</header>
                <div class="welcome-section-divider"></div>
            </div>
       
            <p>
            • You can give away tokens proportionally to our Folders based on computational power or give a set amount to each participant regardless of computational work.
            </p>

            <p>
            • You can choose to give away randomly to our Folders, or even choose to give away to only the Folders who have given a certain amount of computational power.
            </p>

            <p>
            • You set the number of tokens you will give away. There is no minimum or maximum requirement for the number of tokens you give away.
            </p>

            <p>   
            • You pick whether to award your tokens to folders active only on a given day or use the entire multi-year Foldingcoin history as your guide. You can give your tokens away for a limited time, or indefinitely. You have options.
            </p>
        </div>
    </section>

    <section id="use-cases" class="welcome-section">
        
        <div class="welcome-section-content">
            <div class="welcome-section-heading">
                <header>Why Use Merged Folding?</header>
                <div class="welcome-section-divider"></div>
            </div>

            <div class="centered">
                <div class="use-cases">
                    <div class="use-cases__content">
                        <div class="use-cases__content__header">
                            <i class="fa fa-heart"></i>
                            <h3>Encourage Medical Research</h3>
                        
                            <div class="welcome-section-divider"></div>
                        </div>
                        <div class="use-cases__content__text">

                            <p>Promote altruistic scientific research (like Protein Simulations) by providing economic incentives to the Folding@Home network. This is excellent public relations for your company and token as your distribution will be literally helping to encourage life-saving medical research.</p>
                        </div>
                        <a class="use-cases__cta" href="#">
                            <span>Learn More About Folding@Home</span>
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="use-cases">
                    <div class="use-cases__content">
                        <div class="use-cases__content__header">
                            <i class="fa fa-bullhorn"></i>
                            <h3>Promote Your Token</h3>

                            <div class="welcome-section-divider"></div>
                        </div>
                        <div class="use-cases__content__text">
                            <p>Our community of medical research and cryptocurreny enthusiasts is one of the most .... engaged communities .... Giving away your token to our community not only assists with medical research, but also gets the attention of one of the most engaged .... Even do a pre-relaease excited about your new product before it’s released.</p>
                        </div>

                        <a class="use-cases__cta" href="#">
                            <span>See Past Distributions</span>
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="use-cases">
                    <div class="use-cases__content">
                        <div class="use-cases__content__header">
                            <i class="fa fa-check-circle"></i>
                            <h3>It's Easy</h3>

                            <div class="welcome-section-divider"></div>
                        </div>
                        
                        <div class="use-cases__content__text">
                            <p>Create a special token that works within your platform that can only be earned via folding. This helps to incentivize people in our community to be more engaged with your project if there is a token they can only earn via folding.</p>

    <!--                         <p>• Rather than creating your own blockchain requiring people to maintain, you can simply use an already existing mining base that chooses to fold for science instead.</p>

                            <p>• If your project is looking for a way to “Airdrop” your token, and you’re not sure who should receive your token, airdropping to those that are using their mining equipment for folding is a great way for your project to help a charity with a good cause at the same time of promoting your own project. This is a win-win situation for our project, yours, and most importantly medical research.</p> -->

                        </div>

                        <a class="use-cases__cta" href="#">
                            <span>Get Started</span>
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
                <div class="use-cases">
                    <div class="use-cases__content">
                        <div class="use-cases__content__header">
                            <i class="fa fa-percent"></i>
                            <h3>It's Free</h3>

                            <div class="welcome-section-divider"></div>
                        </div>
                        <div class="use-cases__content__text">
                            <p>FoldingCoin, Inc. provides this service for free, you only need to pay the BTC required to confirm the transactions of your tokens to be sent to our participants.</p>
                        </div>
                        <a class="use-cases__cta" href="#">
                            <span>Signup for Free</span>
                            <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>            
        </div>

    </section>

    <section id="how-it-works" class="welcome-section">
        <div class="welcome-section-content">
            <div class="welcome-section-heading">
                <header>How does it work?</header>
                <div class="welcome-section-divider"></div>
            </div>
        
            <div class="how-it-works-row">
                <div class="how-it-works-row__panel">
                    <h3 class="step-1">Create an account</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered">
                    <img src="{{ asset('img/signup-form.PNG') }}" alt=""/>
                </div>
            </div>

            <div class="how-it-works-row">
                <div class="how-it-works-row__panel centered full-screen-only">
                    <img src="{{ asset('img/tokenpass-add-new-address-form.PNG') }}" alt=""/>
                </div>
                <div class="how-it-works-row__panel">
                    <h3 class="step-2">Add Your Wallet Address</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered mobile-only">
                    <img src="{{ asset('img/tokenpass-add-new-address-form.PNG') }}" alt=""/>
                </div>
            </div>

            <div class="how-it-works-row">
                <div class="how-it-works-row__panel">
                    <h3 class="step-3">Create Your Distribution</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered">
                    <img src="{{ asset('img/distribution-form.PNG') }}" alt=""/>
                </div>
            </div>

            <div class="how-it-works-row">
                <div class="how-it-works-row__panel centered full-screen-only">
                    <img src="{{ asset('img/distribution-details.PNG') }}" alt=""/>
                </div>
                <div class="how-it-works-row__panel">
                    <h3 class="step-4">Review the Details of Your Distribution</h3>
                    <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quisque pharetra, nulla a gravida interdum, risus turpis egestas turpis, sit amet convallis elit felis id elit.</p>
                </div>
                <div class="how-it-works-row__panel centered mobile-only">
                    <img src="{{ asset('img/distribution-details.PNG') }}" alt=""/>
                </div>
            </div>
        </div>
    </section>

        <section id="" class="welcome-section">
        
        <div class="welcome-section-content">
            <div class="welcome-section-heading">
                <header>About Merged Folding</header>
                <div class="welcome-section-divider"></div>
            </div>
            
            <p>MergedFolding is a free service that enables anyone to distribute Counterparty tokens to participating Folding@Home users based on their folding contributions</p> 
            <p>The MergedFolding project is a collaboration between the <a href="https://foldingcoin.net/">FoldingCoin, Inc. (FLDC) team</a>, a 501(c)3 public charity, and <a href="https://tokenly.com">Tokenly</a>, a tech-startup that builds blockchain and cryptocurrency software for token distribution, eCommerce, and token-controlled access applications.</p>
            <br>
            <div class="centered">
                <p>
                    
                    <a href="http://foldingcoin.net" target="_blank"><img src="{{ asset('img/fldc/FLDC-Banner2.png') }}" alt=""  style="width: 200px;"></a>
                    <br><br>
                    <a href="https://tokenly.com" target="_blank"><img src="{{ asset('img/Tokenly_Logo_BorderlessA_ldpi.png') }}" alt=""></a>
                </p>
            </div>
        </div>
    </section>

		
@stop


@section('title')
    Token Distributions
@stop
