package it.leonsciurti.questionari.config;

import org.springframework.context.annotation.Configuration;
import org.springframework.web.servlet.config.annotation.CorsRegistry;
import org.springframework.web.servlet.config.annotation.InterceptorRegistry;
import org.springframework.web.servlet.config.annotation.WebMvcConfigurer;

import it.leonsciurti.questionari.sicurezza.AuthInterceptor;

@Configuration
public class WebConfig implements WebMvcConfigurer {

	private final AuthInterceptor authInterceptor;

	public WebConfig(AuthInterceptor authInterceptor) {
		this.authInterceptor = authInterceptor;
	}

	// tutte le rotte /api/** passano dal controllo del token, tranne il login
	// (per forza: quando fai login il token non ce l'hai ancora)
	@Override
	public void addInterceptors(InterceptorRegistry registry) {
		registry.addInterceptor(authInterceptor)
				.addPathPatterns("/api/**")
				.excludePathPatterns("/api/auth/login");
	}

	// CORS: il portale gira su localhost:8888 e l'app Cordova su un altro indirizzo ancora,
	// quindi il browser bloccherebbe le chiamate verso localhost:8080 se non le autorizzo.
	// Posso accettare qualunque origine perche NON uso i cookie: senza il token
	// nell'header una richiesta non ottiene comunque niente.
	@Override
	public void addCorsMappings(CorsRegistry registry) {
		registry.addMapping("/api/**")
				.allowedOrigins("*")
				.allowedMethods("GET", "POST", "PUT", "OPTIONS")
				.allowedHeaders("*");
	}
}
