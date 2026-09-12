package com.edupath.android.presentation.auth

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.edupath.android.core.datastore.SessionStore
import com.edupath.android.data.remote.AuthApi
import com.edupath.android.domain.model.LoginRequest
import com.edupath.android.domain.model.RegisterRequest
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.launch

sealed class AuthState {
    object Idle : AuthState()
    object Loading : AuthState()
    object Success : AuthState()
    data class Error(val message: String) : AuthState()
}

class AuthViewModel(
    private val authApi: AuthApi,
    private val sessionStore: SessionStore
) : ViewModel() {

    private val _authState = MutableStateFlow<AuthState>(AuthState.Idle)
    val authState: StateFlow<AuthState> = _authState.asStateFlow()

    fun login(email: String, pass: String) {
        viewModelScope.launch {
            _authState.value = AuthState.Loading
            try {
                val deviceId = sessionStore.getDeviceId()
                val request = LoginRequest(email, pass, deviceId)
                val response = authApi.login(request)
                
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.token != null && body.refresh_token != null) {
                        sessionStore.saveTokens(body.token, body.refresh_token)
                        body.user?.let {
                            sessionStore.saveUser(it.name, it.email)
                        }
                        _authState.value = AuthState.Success
                    } else {
                        _authState.value = AuthState.Error("Unknown response format")
                    }
                } else {
                    _authState.value = AuthState.Error("Email atau password salah")
                }
            } catch (e: Exception) {
                _authState.value = AuthState.Error(e.message ?: "Connection error")
            }
        }
    }

    fun register(name: String, email: String, pass: String, targetPtn: String) {
        viewModelScope.launch {
            _authState.value = AuthState.Loading
            try {
                val deviceId = sessionStore.getDeviceId()
                val request = RegisterRequest(name, email, pass, targetPtn, deviceId)
                val response = authApi.register(request)
                
                if (response.isSuccessful) {
                    val body = response.body()
                    if (body != null && body.token != null && body.refresh_token != null) {
                        sessionStore.saveTokens(body.token, body.refresh_token)
                        body.user?.let {
                            sessionStore.saveUser(it.name, it.email)
                        }
                        _authState.value = AuthState.Success
                    } else {
                        _authState.value = AuthState.Error("Unknown response format")
                    }
                } else {
                    _authState.value = AuthState.Error("Gagal mendaftar, mungkin email sudah terdaftar")
                }
            } catch (e: Exception) {
                _authState.value = AuthState.Error(e.message ?: "Connection error")
            }
        }
    }

    fun resetState() {
        _authState.value = AuthState.Idle
    }
}
