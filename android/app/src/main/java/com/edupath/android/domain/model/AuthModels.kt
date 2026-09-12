package com.edupath.android.domain.model

import kotlinx.serialization.Serializable

@Serializable
data class LoginRequest(
    val email: String,
    val password: String,
    val device_id: String
)

@Serializable
data class RegisterRequest(
    val name: String,
    val email: String,
    val password: String,
    val target_ptn: String,
    val device_id: String
)

@Serializable
data class LogoutRequest(
    val refresh_token: String
)

@Serializable
data class RefreshRequest(
    val refresh_token: String
)

@Serializable
data class AuthResponse(
    val token: String? = null,
    val refresh_token: String? = null,
    val user: User? = null
)

@Serializable
data class MeResponse(
    val user: User? = null
)

@Serializable
data class BaseResponse(
    val success: Boolean? = null,
    val message: String? = null,
    val error: String? = null
)

@Serializable
data class User(
    val id: String,
    val name: String,
    val email: String,
    val role: String? = null,
    val target_ptn: String? = null
)
