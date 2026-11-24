package it.leonsciurti.questionari.controller;

import org.springframework.http.HttpStatus;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestHeader;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.ResponseStatus;
import org.springframework.web.bind.annotation.RestController;

import it.leonsciurti.questionari.dto.LoginRequest;
import it.leonsciurti.questionari.dto.LoginResponse;
import it.leonsciurti.questionari.servizio.AuthService;
import jakarta.validation.Valid;

// I controller sono solo lo "sportello": ricevono la richiesta e passano il lavoro al service.
@RestController
@RequestMapping("/api/auth")
public class AuthController {

	private final AuthService authService;

	public AuthController(AuthService authService) {
		this.authService = authService;
	}

	// POST /api/auth/login   { "username": "...", "password": "..." }
	@PostMapping("/login")
	public LoginResponse login(@Valid @RequestBody LoginRequest richiesta) {
		return authService.login(richiesta);
	}

	// POST /api/auth/logout  (con il token nell'header) -> 204 senza contenuto
	@PostMapping("/logout")
	@ResponseStatus(HttpStatus.NO_CONTENT)
	public void logout(@RequestHeader("Authorization") String header) {
		authService.logout(header.substring("Bearer ".length()).trim());
	}
}
