package com.edupath.android.data.remote

import com.edupath.android.domain.model.AuthResponse
import com.edupath.android.domain.model.BaseResponse
import com.edupath.android.domain.model.LoginRequest
import com.edupath.android.domain.model.LogoutRequest
import com.edupath.android.domain.model.MeResponse
import com.edupath.android.domain.model.RefreshRequest
import com.edupath.android.domain.model.RegisterRequest
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST

interface AuthApi {
    @POST("auth.php?action=login")
    suspend fun login(
        @Body request: LoginRequest
    ): Response<AuthResponse>

    @POST("auth.php?action=register")
    suspend fun register(
        @Body request: RegisterRequest
    ): Response<AuthResponse>

    @POST("auth.php?action=refresh")
    suspend fun refreshToken(
        @Body request: RefreshRequest
    ): Response<AuthResponse>

    @POST("auth.php?action=logout")
    suspend fun logout(
        @Body request: LogoutRequest
    ): Response<BaseResponse>
    
    @GET("auth.php?action=me")
    suspend fun getMe(): Response<MeResponse>
}
