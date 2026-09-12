package com.edupath.android.core.network

import com.edupath.android.core.config.ApiConfig
import com.edupath.android.core.datastore.SessionStore
import com.edupath.android.data.remote.AuthApi
import com.jakewharton.retrofit2.converter.kotlinx.serialization.asConverterFactory
import kotlinx.coroutines.flow.firstOrNull
import kotlinx.coroutines.runBlocking
import kotlinx.serialization.json.Json
import okhttp3.Interceptor
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit

object RetrofitClient {

    private val json = Json {
        ignoreUnknownKeys = true
        isLenient = true
    }

    // Provider allows lazy initialization of AuthApi to prevent circular dependency in Authenticator
    private lateinit var authApi: AuthApi

    fun create(sessionStore: SessionStore): Retrofit {
        val authInterceptor = Interceptor { chain ->
            val token = runBlocking { sessionStore.accessToken.firstOrNull() }
            val requestBuilder = chain.request().newBuilder()
            
            if (!token.isNullOrEmpty()) {
                requestBuilder.addHeader("Authorization", "Bearer $token")
            }
            
            chain.proceed(requestBuilder.build())
        }

        val loggingInterceptor = HttpLoggingInterceptor().apply {
            level = HttpLoggingInterceptor.Level.BODY
        }

        val authenticator = TokenAuthenticator(sessionStore) { authApi }

        val okHttpClient = OkHttpClient.Builder()
            .addInterceptor(authInterceptor)
            .addInterceptor(loggingInterceptor)
            .authenticator(authenticator)
            .build()

        val retrofit = Retrofit.Builder()
            .baseUrl(ApiConfig.BASE_URL)
            .client(okHttpClient)
            .addConverterFactory(json.asConverterFactory("application/json".toMediaType()))
            .build()

        authApi = retrofit.create(AuthApi::class.java)

        return retrofit
    }
}
