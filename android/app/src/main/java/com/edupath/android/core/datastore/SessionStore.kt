package com.edupath.android.core.datastore

import kotlinx.coroutines.flow.Flow

interface SessionStore {
    val accessToken: Flow<String?>
    val refreshToken: Flow<String?>
    val userName: Flow<String?>
    val userEmail: Flow<String?>
    
    suspend fun getDeviceId(): String
    suspend fun saveTokens(accessToken: String, refreshToken: String)
    suspend fun saveUser(name: String, email: String)
    suspend fun clearSession()
}
