package it.leonsciurti.questionari.repository;

import java.util.Optional;

import org.springframework.data.jpa.repository.JpaRepository;

import it.leonsciurti.questionari.modello.TokenApi;

public interface TokenApiRepository extends JpaRepository<TokenApi, Long> {

	Optional<TokenApi> findByToken(String token);
}
