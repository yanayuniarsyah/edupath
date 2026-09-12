package com.edupath.android.presentation.home

import androidx.lifecycle.ViewModel
import androidx.lifecycle.viewModelScope
import com.edupath.android.core.datastore.SessionStore
import com.edupath.android.data.remote.AuthApi
import com.edupath.android.domain.model.LogoutRequest
import kotlinx.coroutines.flow.MutableStateFlow
import kotlinx.coroutines.flow.StateFlow
import kotlinx.coroutines.flow.asStateFlow
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.launch

sealed class HomeState {
    object Loading : HomeState()
    data class Success(val name: String, val email: String) : HomeState()
    object LoggedOut : HomeState()
}

class HomeViewModel(
    private val authApi: AuthApi,
    private val sessionStore: SessionStore
) : ViewModel() {

    private val _state = MutableStateFlow<HomeState>(HomeState.Loading)
    val state: StateFlow<HomeState> = _state.asStateFlow()

    init {
        loadUser()
    }

    private fun loadUser() {
        viewModelScope.launch {
            val name = sessionStore.userName.first() ?: "Siswa"
            val email = sessionStore.userEmail.first() ?: ""
            _state.value = HomeState.Success(name, email)
        }
    }

    fun logout() {
        viewModelScope.launch {
            _state.value = HomeState.Loading
            try {
                val refreshToken = sessionStore.refreshToken.first()
                if (refreshToken != null) {
                    authApi.logout(LogoutRequest(refreshToken))
                }
            } catch (e: Exception) {
                // Ignore network errors on logout, proceed to clear local session anyway
            } finally {
                sessionStore.clearSession()
                _state.value = HomeState.LoggedOut
            }
        }
    }
}
