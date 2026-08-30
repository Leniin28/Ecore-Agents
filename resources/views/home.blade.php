@extends('layouts.app')

@section('title', 'ECore Agents')

@section('content')
    <section class="hero" id="home">
        <span class="section-eyebrow">Atención inteligente para negocios</span>
        <h1>Bienvenido a ECore Agents</h1>
        <p>Automatiza la atención inicial, gestiona conversaciones desde un inbox centralizado y escala a operadores humanos cuando lo necesites.</p>
        <div class="hero-actions">
            <a class="button" href="{{ route('plans.index') }}">Ver agentes</a>
            <a class="back-button" href="{{ route('plans.custom') }}">Crear agente personalizado</a>
        </div>
    </section>

    <section class="section about" id="about">
        <div class="section-heading">
            <span class="section-eyebrow">Conoce el proyecto</span>
            <h2>Nosotros</h2>
        </div>
        <div class="about-grid">
            <article class="content-card">
                <span class="card-number">01</span>
                <h3>Nuestra historia</h3>
                <p>ECore Agents nace de la necesidad de mejorar la atención al cliente, reducir tiempos de respuesta y facilitar la gestión de conversaciones.</p>
            </article>
            <article class="content-card">
                <span class="card-number">02</span>
                <h3>Nuestra misión</h3>
                <p>Ayudar a los negocios a automatizar su atención al cliente mediante agentes de IA personalizables para WhatsApp.</p>
            </article>
            <article class="content-card">
                <span class="card-number">03</span>
                <h3>Qué ofrecemos</h3>
                <p>Agentes configurables, respuestas automáticas, gestión centralizada de conversaciones y apoyo para escalar la atención a operadores humanos.</p>
            </article>
        </div>
        <p class="online-note">Nuestro servicio funciona completamente en línea, por lo que no contamos con una ubicación física de atención al público.</p>
    </section>

    <section class="section community" id="community">
        <div class="section-heading">
            <span class="section-eyebrow">Comunidad</span>
            <h2>Comentarios de usuarios</h2>
            <p>Contenido demostrativo conservado del frontend académico original.</p>
        </div>
        <div class="community-layout">
            <form class="form-card" method="post" action="#" data-demo-form data-demo-message="Comentario recibido como demostración. No se guardó información.">
                @csrf
                <label for="comment-name">Nombre</label>
                <input id="comment-name" name="name" type="text">
                <label for="comment-text">Comentario</label>
                <textarea id="comment-text" name="comment" rows="4"></textarea>
                <button class="button" type="submit">Publicar demostración</button>
                <p class="form-message" data-form-message role="status" aria-live="polite"></p>
            </form>
            <div class="testimonial-list">
                <article class="testimonial-card">
                    <p>“El catálogo se entiende fácil y el personalizador ayuda a comparar funciones.”</p>
                    <strong>Cliente demo</strong>
                </article>
                <article class="testimonial-card">
                    <p>“Me gustó ver promociones y precios antes de agregar al carrito.”</p>
                    <strong>Negocio invitado</strong>
                </article>
            </div>
        </div>
    </section>

    <section class="section contact" id="contact">
        <div class="section-heading">
            <span class="section-eyebrow">Hablemos</span>
            <h2>Contacto</h2>
        </div>
        <div class="contact-layout">
            <form class="form-card" method="post" action="#" data-demo-form data-demo-message="Mensaje recibido como demostración. El formulario aún no tiene backend.">
                @csrf
                <label for="contact-name">Nombre</label>
                <input id="contact-name" name="name" type="text">
                <label for="contact-email">Correo</label>
                <input id="contact-email" name="email" type="email">
                <label for="contact-message">Mensaje</label>
                <textarea id="contact-message" name="message" rows="5"></textarea>
                <button class="button" type="submit">Enviar demostración</button>
                <p class="form-message" data-form-message role="status" aria-live="polite"></p>
            </form>
            <aside class="contact-note">
                <span class="section-eyebrow">Etapa 1</span>
                <h3>Base visual preparada</h3>
                <p>Este formulario conserva la experiencia del proyecto anterior sin fingir un envío real. Se conectará al backend en una etapa posterior.</p>
            </aside>
        </div>
    </section>
@endsection
