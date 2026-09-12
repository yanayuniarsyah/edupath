package com.edupath.android.core.network

import com.edupath.android.core.datastore.SessionStore
import com.edupath.android.data.remote.AuthApi
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.runBlocking
import kotlinx.coroutines.sync.Mutex
import kotlinx.coroutines.sync.withLock
import okhttp3.Authenticator
import okhttp3.Request
import okhttp3.Response
import okhttp3.Route

class TokenAuthenticator(
    private val sessionStore: SessionStore,
    private val authApiProvider: () -> AuthApi
) : Authenticator {

    private val mutex = Mutex()

    override fun authenticate(route: Route?, response: Response): Request? {
        // If the request already has an Authorization header that failed, we need to refresh.
        // We use runBlocking here because Authenticator is called synchronously by OkHttp in a background thread.
        return runBlocking {
            val currentToken = sessionStore.accessToken.firstOrNull()
            
            // Single-flight refresh logic
            mutex.withLock {
                val updatedToken = sessionStore.accessToken.firstOrNull()
                
                // If token was refreshed by another request while we were waiting for the lock
                if (updatedToken != null && updatedToken != currentToken) {
                    return@runBlocking response.request.newBuilder()
                        .header("Authorization", "Bearer $updatedToken")
                        .build()
                }

                // Need to refresh
                val refreshToken = sessionStore.refreshToken.firstOrNull()
                if (refreshToken.isNullOrEmpty()) {
                    sessionStore.clearSession()
                    return@runBlocking null
                }

                val authApi = authApiProvider()
                try {
                    val refreshResponse = authApi.refreshToken(refreshToken)
                    if (refreshResponse.isSuccessful) {
                        val newAccessToken = refreshResponse.body()?.data?.access_token
                        val newRefreshToken = refreshResponse.body()?.data?.refresh_token
                        if (newAccessToken != null && newRefreshToken != null) {
                            sessionStore.saveTokens(newAccessToken, newRefreshToken)
                            return@runBlocking response.request.newBuilder()
                                .header("Authorization", "Bearer $newAccessToken")
                                .build()
                        }
                    }
                } catch (e: Exception) {
                    // Ignore and fall through to clear session
                }
                
                // If refresh failed
                sessionStore.clearSession()
                null
            }
        }
    }
}
